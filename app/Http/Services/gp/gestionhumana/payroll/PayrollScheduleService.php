<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exceptions\PayrollValidationException;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollScheduleResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\GeneralMaster;
use App\Models\gp\gestionhumana\payroll\AttendanceRule;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollCalculationDetail;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollScheduleService extends BaseService implements BaseServiceInterface
{
  protected PayrollSummaryService $summaryService;

  public function __construct(PayrollSummaryService $summaryService)
  {
    $this->summaryService = $summaryService;
  }

  /**
   * Get all schedules with filters and pagination
   */
  public function list(Request $request)
  {
    $query = PayrollSchedule::with(['worker', 'period']);

    return $this->getFilteredResults(
      $query,
      $request,
      PayrollSchedule::filters,
      PayrollSchedule::sorts,
      PayrollScheduleResource::class,
    );
  }

  /**
   * Find a schedule by ID
   */
  public function find($id)
  {
    $schedule = PayrollSchedule::with(['worker', 'period'])->find($id);
    if (!$schedule) {
      throw new Exception('Schedule not found');
    }
    return $schedule;
  }

  /**
   * Show a schedule by ID
   */
  public function show($id)
  {
    return new PayrollScheduleResource($this->find($id));
  }

  /**
   * Resolve the date range for schedule filtering based on biweekly half.
   *
   * biweekly = 1 → start_date  ... biweekly_date
   * biweekly = 2 → biweekly_date+1 ... end_date
   * biweekly = null → start_date ... end_date (full month)
   *
   * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
   */
  private function getDateRangeForBiweekly(PayrollPeriod $period, ?int $biweekly): array
  {
    if ($biweekly === 1 && $period->biweekly_date) {
      return [$period->start_date, $period->biweekly_date];
    }

    if ($biweekly === 2 && $period->biweekly_date) {
      return [$period->biweekly_date->copy()->addDay(), $period->end_date];
    }

    return [$period->start_date, $period->end_date];
  }

  /**
   * Calculate hours for a schedule based on code and worker
   */
  private function calculateHours($code, $worker, $status = null)
  {
    // Si el código es "F" (Falta) o "LSGH" Y el status es ABSENT, no se paga nada - 0 horas trabajadas
    if (($code === 'F' || $code === 'LSGH') && $status === PayrollSchedule::STATUS_ABSENT) {
      return ['hours_worked' => 0, 'extra_hours' => 0];
    }

    $rule = AttendanceRule::where('code', $code)->first();
    $workingHours = GeneralMaster::find(GeneralMaster::WORKING_HOURS_ID)->value ?? 8;
    $horasJornada = (float)($worker->horas_jornada ?: $workingHours);

    if (!$rule) {
      return ['hours_worked' => $horasJornada, 'extra_hours' => 0];
    }

    $horas = $rule->use_shift ? $horasJornada : $workingHours;

    // Si son horas normales
    if ($horasJornada <= $workingHours) {
      return ['hours_worked' => $horas, 'extra_hours' => 0];
    }

    // Si hay horas extras
    return ['hours_worked' => $workingHours, 'extra_hours' => $horasJornada - $workingHours];
  }

  /**
   * Create a new schedule
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      // Validate worker exists
      $worker = Worker::find($data['worker_id']);
      if (!$worker) {
        throw new Exception('Worker not found');
      }

      // Validate period exists and is modifiable
      $period = PayrollPeriod::find($data['period_id']);
      if (!$period) {
        throw new Exception('Period not found');
      }
      if ($period->status !== PayrollPeriod::STATUS_OPEN) {
        throw new Exception('No se puede agregar horario: el período debe estar en estado ABIERTO (OPEN). Estado actual: ' . $period->status);
      }

      // Check for duplicate
      $existingSchedule = PayrollSchedule::where('worker_id', $data['worker_id'])
        ->where('work_date', $data['work_date'])
        ->first();

      if ($existingSchedule) {
        throw new Exception('Schedule already exists for this worker on this date');
      }

      // Calculate hours
      $status = $data['status'] ?? PayrollSchedule::STATUS_WORKED;
      $hours = $this->calculateHours($data['code'], $worker, $status);

      $schedule = PayrollSchedule::create([
        'worker_id' => $data['worker_id'],
        'code' => $data['code'],
        'period_id' => $data['period_id'],
        'work_date' => $data['work_date'],
        'hours_worked' => $hours['hours_worked'],
        'extra_hours' => $hours['extra_hours'],
        'notes' => $data['notes'] ?? null,
        'status' => $status,
      ]);

      DB::commit();
      return new PayrollScheduleResource($schedule->load(['worker', 'period']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Create schedules in bulk
   */
  public function storeBulk(array $data)
  {
    try {
      DB::beginTransaction();

      $periodId = $data['period_id'];
      $schedules = $data['schedules'];

      // Validate period exists and is modifiable
      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }
      if ($period->status !== PayrollPeriod::STATUS_OPEN) {
        throw new Exception('No se pueden agregar horarios: el período debe estar en estado ABIERTO (OPEN). Estado actual: ' . $period->status);
      }

      $createdSchedules = [];
      $errors = [];

      foreach ($schedules as $index => $scheduleData) {
        try {
          // Validate worker exists
          $worker = Worker::find($scheduleData['worker_id']);
          if (!$worker) {
            $errors[] = "Row {$index}: Worker not found";
            continue;
          }

          // Check for duplicate
          $existingSchedule = PayrollSchedule::where('worker_id', $scheduleData['worker_id'])
            ->where('work_date', $scheduleData['work_date'])
            ->first();

          // Calculate hours
          $status = $scheduleData['status'] ?? PayrollSchedule::STATUS_WORKED;
          $hours = $this->calculateHours($scheduleData['code'], $worker, $status);

          if ($existingSchedule) {
            // Update existing schedule
            $existingSchedule->update([
              'code' => $scheduleData['code'],
              'hours_worked' => $hours['hours_worked'],
              'extra_hours' => $hours['extra_hours'],
              'notes' => $scheduleData['notes'] ?? null,
              'status' => $status,
            ]);
            $createdSchedules[] = $existingSchedule;
          } else {
            // Create new schedule
            $schedule = PayrollSchedule::create([
              'worker_id' => $scheduleData['worker_id'],
              'code' => $scheduleData['code'],
              'period_id' => $periodId,
              'work_date' => $scheduleData['work_date'],
              'hours_worked' => $hours['hours_worked'],
              'extra_hours' => $hours['extra_hours'],
              'notes' => $scheduleData['notes'] ?? null,
              'status' => $status,
            ]);
            $createdSchedules[] = $schedule;
          }
        } catch (Exception $e) {
          $errors[] = "Row {$index}: " . $e->getMessage();
        }
      }

      DB::commit();

      return [
        'created_count' => count($createdSchedules),
        'errors' => $errors,
        'schedules' => PayrollScheduleResource::collection(
          collect($createdSchedules)->map(fn($s) => $s->load(['worker', 'period']))
        ),
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a schedule
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $schedule = $this->find($data['id']);

      // Validate period is modifiable
      if ($schedule->period->status !== PayrollPeriod::STATUS_OPEN) {
        throw new Exception('No se puede actualizar el horario: el período debe estar en estado ABIERTO (OPEN). Estado actual: ' . $schedule->period->status);
      }

      $updateData = [
        'code' => $data['code'] ?? $schedule->code,
        'notes' => $data['notes'] ?? $schedule->notes,
        'status' => $data['status'] ?? $schedule->status,
      ];

      // Recalculate hours if code or status changed
      if ((isset($data['code']) && $data['code'] !== $schedule->code) ||
          (isset($data['status']) && $data['status'] !== $schedule->status)) {
        $hours = $this->calculateHours(
          $updateData['code'],
          $schedule->worker,
          $updateData['status']
        );
        $updateData['hours_worked'] = $hours['hours_worked'];
        $updateData['extra_hours'] = $hours['extra_hours'];
      }

      $schedule->update($updateData);

      DB::commit();
      return new PayrollScheduleResource($schedule->fresh()->load(['worker', 'period']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a schedule
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      $schedule = $this->find($id);

      // Validate period is modifiable
      if ($schedule->period->status !== PayrollPeriod::STATUS_OPEN) {
        throw new Exception('No se puede eliminar el horario: el período debe estar en estado ABIERTO (OPEN). Estado actual: ' . $schedule->period->status);
      }

      $schedule->delete();

      DB::commit();
      return response()->json(['message' => 'Schedule deleted successfully']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get summary by period using attendance codes and rules
   *
   * Calculation logic:
   * 1. Group schedules by worker_id and code
   * 2. Count days worked per code
   * 3. For each code, get AttendanceRules
   * 4. Calculate cost per rule:
   *    - If use_shift = 1: hours = worker.horas_jornada
   *    - If use_shift = 0: hours = rule.hours
   *    - base_hour_value = worker.sueldo / 30 / worker.horas_jornada
   *    - If hour_type = NOCTURNO: apply 35% surcharge (multiply by 1.35)
   *    - Apply rule multiplier
   *    - total = hours × hour_value × multiplier × days_worked
   *    - If pay = 0: subtract instead of add
   */
  public function getSummaryByPeriod(int $periodId, ?int $biweekly = null)
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    [$dateFrom, $dateTo] = $this->getDateRangeForBiweekly($period, $biweekly);

    // Get all worked schedules for this period (filtered by biweekly half if provided)
    $schedules = PayrollSchedule::with(['worker'])
      ->where('period_id', $periodId)
      ->where('status', PayrollSchedule::STATUS_WORKED)
      ->where('work_date', '>=', $dateFrom)
      ->where('work_date', '<=', $dateTo)
      ->get();

    // Get all attendance rules
    $attendanceRules = AttendanceRule::all()->groupBy('code');

    $summary = $schedules->groupBy('worker_id')->map(function ($workerSchedules) use ($attendanceRules) {
      $worker = $workerSchedules->first()->worker;

      // Worker salary and shift info
      $sueldo = (float)($worker->sueldo ?? 0);
      $horasJornada = (float)($worker->horas_jornada ?: 8);

      if ($sueldo == 0 || $horasJornada == 0) {
        return null; // Skip worker with invalid salary or shift hours
      }

      // Base hour value
      //$valorHoraBase = $sueldo / 30 / $horasJornada;
      $valorHoraBase = $sueldo / 30 / 8;

      // Night surcharge constant (35%)
      $recargoNocturno = 1.35;

      // Group by code and count days
      $codeGroups = $workerSchedules->groupBy('code');

      $details = [];
      $totalAmount = 0;

      foreach ($codeGroups as $code => $codeSchedules) {
        $diasTrabajados = $codeSchedules->count();

        // Get rules for this code
        $rules = $attendanceRules->get($code);

        if (!$rules || $rules->isEmpty()) {
          continue; // Skip if no rules defined for this code
        }

        foreach ($rules as $rule) {
          // Determine hours to use
          $horas = $rule->use_shift ? $horasJornada : (float)$rule->hours;

          // Calculate hour value with night surcharge if applicable
          $valorHora = $valorHoraBase;
          if (strtoupper($rule->hour_type) === 'NOCTURNO') {
            $valorHora *= $recargoNocturno;
          }

          // Apply multiplier
          $valorHora *= (float)$rule->multiplier;

          // Calculate total for this rule
          $total = $horas * $valorHora * $diasTrabajados;

          // If pay = 0, subtract instead of add
          if (!$rule->pay) {
            $total = -$total;
          }

          $totalAmount += $total;

          $details[] = [
            'code' => $code,
            'hour_type' => $rule->hour_type,
            'hours' => round($horas, 2),
            'multiplier' => round((float)$rule->multiplier, 4),
            'pay' => $rule->pay,
            'use_shift' => $rule->use_shift,
            'hour_value' => round($valorHora, 2),
            'days_worked' => $diasTrabajados,
            'total' => round($total, 2),
          ];
        }
      }

      return [
        'worker_id' => $worker->id,
        'worker_name' => $worker->nombre_completo,
        'salary' => round($sueldo, 2),
        'shift_hours' => round($horasJornada, 2),
        'base_hour_value' => round($valorHoraBase, 2),
        'details' => $details,
        'total_amount' => round($totalAmount, 2),
      ];
    })->filter()->values();

    return [
      'period' => new PayrollPeriodResource($period),
      'biweekly' => $biweekly,
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
      'workers_count' => $summary->count(),
      'summary' => $summary,
    ];
  }

  /**
   * Validates that all workers assigned at least once in the given date range
   * have a schedule entry for every single day in that range.
   * Throws PayrollValidationException with the list of workers and missing dates if not.
   */
  private function validateAllDaysFilled(PayrollPeriod $period, $dateFrom, $dateTo): void
  {
    $dateFromCarbon = Carbon::parse($dateFrom);
    $dateToCarbon   = Carbon::parse($dateTo);

    // Workers that appear at least once in this date range
    $workerIds = PayrollSchedule::where('period_id', $period->id)
      ->where('work_date', '>=', $dateFromCarbon)
      ->where('work_date', '<=', $dateToCarbon)
      ->distinct()
      ->pluck('worker_id');

    if ($workerIds->isEmpty()) {
      return;
    }

    // All calendar dates in the range
    $allDates = [];
    $cursor = $dateFromCarbon->copy();
    while ($cursor->lte($dateToCarbon)) {
      $allDates[] = $cursor->format('Y-m-d');
      $cursor->addDay();
    }

    // Existing schedules grouped by worker
    $existingByWorker = PayrollSchedule::with('worker')
      ->where('period_id', $period->id)
      ->where('work_date', '>=', $dateFromCarbon)
      ->where('work_date', '<=', $dateToCarbon)
      ->whereIn('worker_id', $workerIds)
      ->get()
      ->groupBy('worker_id');

    $missingByWorker = [];

    foreach ($workerIds as $workerId) {
      $workerSchedules = $existingByWorker->get($workerId, collect());
      $filledDates     = $workerSchedules->map(fn($s) => Carbon::parse($s->work_date)->format('Y-m-d'))->toArray();
      $missingDates    = array_values(array_diff($allDates, $filledDates));

      if (empty($missingDates)) {
        continue;
      }

      $worker = $workerSchedules->isNotEmpty()
        ? $workerSchedules->first()->worker
        : Worker::find($workerId);

      $missingByWorker[] = [
        'worker_id'    => $workerId,
        'worker_name'  => $worker->nombre_completo ?? "Worker #{$workerId}",
        'missing_dates' => $missingDates,
      ];
    }

    if (!empty($missingByWorker)) {
      $names = implode(', ', array_column($missingByWorker, 'worker_name'));
      throw new PayrollValidationException(
        "Faltan días por llenar para los siguientes trabajadores: {$names}",
        ['workers_with_missing_days' => $missingByWorker]
      );
    }
  }

  /**
   * Create PayrollCalculation + detail records for all workers with schedules in the given date range.
   * Used internally by generate and recalculate.
   *
   * @param PayrollPeriod $period
   * @param int|null $biweekly Value to stamp on each created calculation (1, 2, or null)
   * @param mixed $dateFrom
   * @param mixed $dateTo
   * @param \Illuminate\Support\Collection $attendanceRules Grouped by code
   * @param array $errors Passed by reference — worker-level errors are appended here
   * @return int[]  IDs of created PayrollCalculation records
   */
  private function createCalculationsForPeriod(
    PayrollPeriod $period,
    ?int          $biweekly,
                  $dateFrom,
                  $dateTo,
                  $attendanceRules,
    array         &$errors
  ): array
  {
    $schedules = PayrollSchedule::with(['worker'])
      ->where('period_id', $period->id)
      ->where('status', PayrollSchedule::STATUS_WORKED)
      ->where('work_date', '>=', $dateFrom)
      ->where('work_date', '<=', $dateTo)
      ->get();

    if ($schedules->isEmpty()) {
      return [];
    }

    $createdIds = [];
    $recargoNocturno = 1.35;

    foreach ($schedules->groupBy('worker_id') as $workerId => $workerSchedules) {
      try {
        $worker = $workerSchedules->first()->worker;
        $sueldo = (float)($worker->sueldo ?? 0);
        $horasJornada = (float)($worker->horas_jornada ?: 8);

        if ($sueldo == 0 || $horasJornada == 0) {
          $errors[] = "Worker {$worker->nombre_completo}: Invalid salary or shift hours";
          continue;
        }

        $valorHoraBase = $sueldo / 30 / 8;

        $calculation = PayrollCalculation::create([
          'period_id' => $period->id,
          'biweekly' => $biweekly,
          'worker_id' => $workerId,
          'company_id' => $worker->company_id ?? null,
          'sede_id' => $worker->sede_id ?? null,
          'salary' => $sueldo,
          'shift_hours' => $horasJornada,
          'base_hour_value' => $valorHoraBase,
          'days_worked' => $workerSchedules->count(),
          'status' => PayrollCalculation::STATUS_CALCULATED,
          'calculated_at' => now(),
          'calculated_by' => auth()->id(),
        ]);

        $totalEarnings = 0;
        $calculationOrder = 1;

        foreach ($workerSchedules->groupBy('code') as $code => $codeSchedules) {
          $diasTrabajados = $codeSchedules->count();
          $rules = $attendanceRules->get($code);

          if (!$rules || $rules->isEmpty()) {
            continue;
          }

          foreach ($rules as $rule) {
            $horas = $rule->use_shift ? $horasJornada : (float)$rule->hours;
            $valorHora = $valorHoraBase;
            if (strtoupper($rule->hour_type) === 'NOCTURNO') {
              $valorHora *= $recargoNocturno;
            }
            $valorHoraConMultiplicador = $valorHora * (float)$rule->multiplier;
            $total = $horas * $valorHoraConMultiplicador * $diasTrabajados;
            if (!$rule->pay) {
              $total = -$total;
            }
            $totalEarnings += $total;

            PayrollCalculationDetail::create([
              'calculation_id' => $calculation->id,
              'concept_id' => null,
              'concept_code' => $code,
              'concept_name' => $rule->description ?? $code,
              'type' => $rule->pay ? 'EARNING' : 'DEDUCTION',
              'category' => 'ATTENDANCE',
              'hour_type' => $rule->hour_type,
              'hours' => $horas,
              'days_worked' => $diasTrabajados,
              'multiplier' => (float)$rule->multiplier,
              'use_shift' => $rule->use_shift,
              'hour_value' => $valorHoraConMultiplicador,
              'amount' => $total,
              'calculation_order' => $calculationOrder++,
            ]);
          }
        }

        $calculation->update([
          'total_earnings' => $totalEarnings > 0 ? $totalEarnings : 0,
          'total_deductions' => $totalEarnings < 0 ? abs($totalEarnings) : 0,
        ]);

        $this->summaryService->persist($calculation->load('details'));
        $createdIds[] = $calculation->id;
      } catch (Exception $e) {
        $errors[] = "Worker ID {$workerId}: " . $e->getMessage();
      }
    }

    return $createdIds;
  }

  /**
   * Force-delete all calculations (and their details) for a given period + biweekly value.
   * Throws if any are APPROVED or PAID.
   */
  private function deleteCalculationsForBiweekly(int $periodId, ?int $biweekly): void
  {
    $query = PayrollCalculation::withTrashed()->where('period_id', $periodId);
    $biweekly === null ? $query->whereNull('biweekly') : $query->where('biweekly', $biweekly);
    $existing = $query->get();

    $locked = $existing->filter(fn($c) => in_array($c->status, [
      PayrollCalculation::STATUS_APPROVED,
      PayrollCalculation::STATUS_PAID,
    ]));

    if ($locked->count() > 0) {
      $label = $biweekly ? "biweekly={$biweekly}" : 'full month';
      throw new Exception("Cannot recalculate ({$label}): {$locked->count()} calculations are already APPROVED or PAID.");
    }

    if ($existing->isNotEmpty()) {
      PayrollCalculationDetail::whereIn('calculation_id', $existing->pluck('id'))->forceDelete();
      $deleteQuery = PayrollCalculation::withTrashed()->where('period_id', $periodId);
      $biweekly === null ? $deleteQuery->whereNull('biweekly') : $deleteQuery->where('biweekly', $biweekly);
      $deleteQuery->forceDelete();
    }
  }

  /**
   * Generate payroll calculations for a period.
   *
   * biweekly=1  → creates records with biweekly=1 (primera quincena)
   * biweekly=2  → creates records with biweekly=2 (segunda quincena)
   * biweekly=null + period has biweekly_date
   *             → creates records for biweekly=1, biweekly=2, AND biweekly=null (consolidado)
   * biweekly=null + no biweekly_date
   *             → creates records with biweekly=null (mes completo)
   *
   * @param int $periodId
   * @return array
   * @throws Exception
   */
  public function generatePayrollCalculations(int $periodId, ?int $biweekly = null)
  {
    try {
      DB::beginTransaction();

      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }

      if ($biweekly !== null && !$period->biweekly_date) {
        throw new Exception('The period does not have a biweekly date configured.');
      }

      $attendanceRules = AttendanceRule::all()->groupBy('code');
      $errors = [];
      $allCreatedIds = [];

      // Validate all assigned workers have every day filled before processing
      if ($biweekly === null && $period->biweekly_date) {
        [$vFrom1, $vTo1] = $this->getDateRangeForBiweekly($period, 1);
        [$vFrom2, $vTo2] = $this->getDateRangeForBiweekly($period, 2);
        $this->validateAllDaysFilled($period, $vFrom1, $vTo1);
        $this->validateAllDaysFilled($period, $vFrom2, $vTo2);
      } else {
        [$vFrom, $vTo] = $this->getDateRangeForBiweekly($period, $biweekly);
        $this->validateAllDaysFilled($period, $vFrom, $vTo);
      }

      if ($biweekly === null && $period->biweekly_date) {
        // Period with biweekly split: create only biweekly=1 and biweekly=2 (no null consolidado)
        $existingB1 = PayrollCalculation::where('period_id', $periodId)->where('biweekly', 1)->count();
        $existingB2 = PayrollCalculation::where('period_id', $periodId)->where('biweekly', 2)->count();

        if ($existingB1 > 0 || $existingB2 > 0) {
          $parts = array_values(array_filter([
            $existingB1 > 0 ? 'biweekly=1' : null,
            $existingB2 > 0 ? 'biweekly=2' : null,
          ]));
          throw new Exception('Calculations already exist for this period (' . implode(', ', $parts) . '). Use the recalculate endpoint to update them.');
        }

        [$from1, $to1] = $this->getDateRangeForBiweekly($period, 1);
        $allCreatedIds = array_merge($allCreatedIds, $this->createCalculationsForPeriod($period, 1, $from1, $to1, $attendanceRules, $errors));

        [$from2, $to2] = $this->getDateRangeForBiweekly($period, 2);
        $allCreatedIds = array_merge($allCreatedIds, $this->createCalculationsForPeriod($period, 2, $from2, $to2, $attendanceRules, $errors));
      } else {
        // Single biweekly half (or full month without biweekly_date)
        $existingQuery = PayrollCalculation::where('period_id', $periodId);
        $biweekly === null ? $existingQuery->whereNull('biweekly') : $existingQuery->where('biweekly', $biweekly);

        if ($existingQuery->count() > 0) {
          $halfLabel = $biweekly ? "biweekly={$biweekly}" : 'full month';
          throw new Exception("Calculations already exist for this period ({$halfLabel}). Use the recalculate endpoint to update them.");
        }

        [$dateFrom, $dateTo] = $this->getDateRangeForBiweekly($period, $biweekly);
        $allCreatedIds = $this->createCalculationsForPeriod($period, $biweekly, $dateFrom, $dateTo, $attendanceRules, $errors);
      }

      if (empty($allCreatedIds)) {
        throw new Exception('No worked schedules found for this period');
      }

      if ($period->status !== PayrollPeriod::STATUS_CALCULATED) {
        $period->update(['status' => PayrollPeriod::STATUS_CALCULATED]);
      }

      DB::commit();

      return [
        'success' => true,
        'period_id' => $periodId,
        'calculations_created' => count($allCreatedIds),
        'calculation_ids' => $allCreatedIds,
        'errors' => $errors,
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Recalculate payroll calculations for a period.
   * Deletes existing calculations and regenerates them based on current attendance schedules.
   *
   * biweekly=1 or 2 → deletes and regenerates only that half
   * biweekly=null + period has biweekly_date
   *                → deletes and regenerates all three (1, 2, and null consolidado)
   * biweekly=null + no biweekly_date
   *                → deletes and regenerates the full-month set
   *
   * @param int $periodId
   * @return array
   * @throws Exception
   */
  public function recalculatePayrollCalculations(int $periodId, ?int $biweekly = null)
  {
    try {
      DB::beginTransaction();

      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }

      if ($biweekly !== null && !$period->biweekly_date) {
        throw new Exception('The period does not have a biweekly date configured.');
      }

      $isFullWithSplit = $biweekly === null && $period->biweekly_date;

      // Validate all assigned workers have every day filled before processing
      if ($isFullWithSplit) {
        [$vFrom1, $vTo1] = $this->getDateRangeForBiweekly($period, 1);
        [$vFrom2, $vTo2] = $this->getDateRangeForBiweekly($period, 2);
        $this->validateAllDaysFilled($period, $vFrom1, $vTo1);
        $this->validateAllDaysFilled($period, $vFrom2, $vTo2);
      } else {
        [$vFrom, $vTo] = $this->getDateRangeForBiweekly($period, $biweekly);
        $this->validateAllDaysFilled($period, $vFrom, $vTo);
      }

      // Delete existing calculations (throws if any are APPROVED/PAID)
      if ($isFullWithSplit) {
        $this->deleteCalculationsForBiweekly($periodId, 1);
        $this->deleteCalculationsForBiweekly($periodId, 2);
      } else {
        $this->deleteCalculationsForBiweekly($periodId, $biweekly);
      }

      $attendanceRules = AttendanceRule::all()->groupBy('code');
      $errors = [];
      $allCreatedIds = [];

      if ($isFullWithSplit) {
        [$from1, $to1] = $this->getDateRangeForBiweekly($period, 1);
        $allCreatedIds = array_merge($allCreatedIds, $this->createCalculationsForPeriod($period, 1, $from1, $to1, $attendanceRules, $errors));

        [$from2, $to2] = $this->getDateRangeForBiweekly($period, 2);
        $allCreatedIds = array_merge($allCreatedIds, $this->createCalculationsForPeriod($period, 2, $from2, $to2, $attendanceRules, $errors));
      } else {
        [$dateFrom, $dateTo] = $this->getDateRangeForBiweekly($period, $biweekly);
        $allCreatedIds = $this->createCalculationsForPeriod($period, $biweekly, $dateFrom, $dateTo, $attendanceRules, $errors);
      }

      if (empty($allCreatedIds)) {
        throw new Exception('No worked schedules found for this period');
      }

      DB::commit();

      return [
        'success' => true,
        'period_id' => $periodId,
        'calculations_created' => count($allCreatedIds),
        'calculation_ids' => $allCreatedIds,
        'errors' => $errors,
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get daily attendances for all workers in a period
   * Returns attendance data grouped by worker with daily codes and summary
   *
   * @param int $periodId
   * @return array
   * @throws Exception
   */
  public function getAttendancesByPeriod(int $periodId, ?int $biweekly = null)
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    [$dateFrom, $dateTo] = $this->getDateRangeForBiweekly($period, $biweekly);

    // Get all schedules for this period with worker information
    $schedules = PayrollSchedule::with(['worker'])
      ->where('period_id', $periodId)
      ->where('work_date', '>=', $dateFrom)
      ->where('work_date', '<=', $dateTo)
      ->orderBy('work_date', 'asc')
      ->get();

    if ($schedules->isEmpty()) {
      return [
        'period_id' => $periodId,
        'period_name' => $period->name ?? "{$period->start_date} - {$period->end_date}",
        'start_date' => $dateFrom,
        'end_date' => $dateTo,
        'biweekly_date' => $period->biweekly_date,
        'biweekly' => $biweekly,
        'attendances' => [],
      ];
    }

    // Group schedules by worker
    $attendancesByWorker = $schedules->groupBy('worker_id')->map(function ($workerSchedules) {
      $worker = $workerSchedules->first()->worker;

      // Prepare daily attendances array
      $dailyAttendances = $workerSchedules->map(function ($schedule) {
        return [
          'date' => $schedule->work_date->format('Y-m-d'),
          'code' => $schedule->code,
          'status' => $schedule->status,
        ];
      })->values();

      // Count occurrences of each code for summary
      $codeCounts = $workerSchedules->groupBy('code')->map(function ($codeGroup) {
        return $codeGroup->count();
      });

      return [
        'worker_id' => $worker->id,
        'worker_name' => $worker->nombre_completo ?? '',
        'document_number' => $worker->vat ?? '',
        'daily_attendances' => $dailyAttendances,
        'summary' => [
          'codes' => $codeCounts,
          'total_days' => $dailyAttendances->count(),
        ],
      ];
    })->values();

    return [
      'period_id' => $periodId,
      'period_name' => $period->name ?? "{$period->start_date} - {$period->end_date}",
      'start_date' => $dateFrom,
      'end_date' => $dateTo,
      'biweekly_date' => $period->biweekly_date,
      'biweekly' => $biweekly,
      'total_workers' => $attendancesByWorker->count(),
      'attendances' => $attendancesByWorker,
    ];
  }
}
