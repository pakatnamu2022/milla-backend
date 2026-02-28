<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollScheduleResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\AttendanceRule;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollCalculationDetail;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollScheduleService extends BaseService implements BaseServiceInterface
{
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
   * Calculate hours for a schedule based on code and worker
   */
  private function calculateHours($code, $worker)
  {
    $rule = AttendanceRule::where('code', $code)->first();
    $horasJornada = (float)($worker->horas_jornada ?: 8);

    if (!$rule) {
      return ['hours_worked' => $horasJornada, 'extra_hours' => 0];
    }

    $horas = $rule->use_shift ? $horasJornada : (float)$rule->hours;

    // Si son horas normales
    if ($horas <= $horasJornada) {
      return ['hours_worked' => $horas, 'extra_hours' => 0];
    }

    // Si hay horas extras
    return ['hours_worked' => $horasJornada, 'extra_hours' => $horas - $horasJornada];
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
      if (!$period->canModify()) {
        throw new Exception('Cannot add schedule: period is in ' . $period->status . ' status');
      }

      // Check for duplicate
      $existingSchedule = PayrollSchedule::where('worker_id', $data['worker_id'])
        ->where('work_date', $data['work_date'])
        ->first();

      if ($existingSchedule) {
        throw new Exception('Schedule already exists for this worker on this date');
      }

      // Calculate hours
      $hours = $this->calculateHours($data['code'], $worker);

      $schedule = PayrollSchedule::create([
        'worker_id' => $data['worker_id'],
        'code' => $data['code'],
        'period_id' => $data['period_id'],
        'work_date' => $data['work_date'],
        'hours_worked' => $hours['hours_worked'],
        'extra_hours' => $hours['extra_hours'],
        'notes' => $data['notes'] ?? null,
        'status' => $data['status'] ?? PayrollSchedule::STATUS_WORKED,
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
      if (!$period->canModify()) {
        throw new Exception('Cannot add schedules: period is in ' . $period->status . ' status');
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
          $hours = $this->calculateHours($scheduleData['code'], $worker);

          if ($existingSchedule) {
            // Update existing schedule
            $existingSchedule->update([
              'code' => $scheduleData['code'],
              'hours_worked' => $hours['hours_worked'],
              'extra_hours' => $hours['extra_hours'],
              'notes' => $scheduleData['notes'] ?? null,
              'status' => $scheduleData['status'] ?? PayrollSchedule::STATUS_WORKED,
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
              'status' => $scheduleData['status'] ?? PayrollSchedule::STATUS_WORKED,
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
      if (!$schedule->period->canModify()) {
        throw new Exception('Cannot update schedule: period is in ' . $schedule->period->status . ' status');
      }

      $updateData = [
        'code' => $data['code'] ?? $schedule->code,
        'notes' => $data['notes'] ?? $schedule->notes,
        'status' => $data['status'] ?? $schedule->status,
      ];

      // Recalculate hours if code changed
      if (isset($data['code']) && $data['code'] !== $schedule->code) {
        $hours = $this->calculateHours($data['code'], $schedule->worker);
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
      if (!$schedule->period->canModify()) {
        throw new Exception('Cannot delete schedule: period is in ' . $schedule->period->status . ' status');
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
  public function getSummaryByPeriod(int $periodId)
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    // Get all worked schedules for this period
    $schedules = PayrollSchedule::with(['worker'])
      ->where('period_id', $periodId)
      ->where('status', PayrollSchedule::STATUS_WORKED)
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
      $valorHoraBase = $sueldo / 30 / $horasJornada;

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
      'workers_count' => $summary->count(),
      'summary' => $summary,
    ];
  }

  /**
   * Generate payroll calculations for a period
   * Creates PayrollCalculation and PayrollCalculationDetail records based on attendance schedules
   *
   * @param int $periodId
   * @return array
   * @throws Exception
   */
  public function generatePayrollCalculations(int $periodId)
  {
    try {
      DB::beginTransaction();

      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }

      // Check if calculations already exist for this period
      $existingCount = PayrollCalculation::where('period_id', $periodId)->count();
      if ($existingCount > 0) {
        throw new Exception("Ya existen cálculos para este período. Utilice el punto final de recalculación para actualizarlos.");
      }

      // Get all worked schedules for this period
      $schedules = PayrollSchedule::with(['worker'])
        ->where('period_id', $periodId)
        ->where('status', PayrollSchedule::STATUS_WORKED)
        ->get();

      if ($schedules->isEmpty()) {
        throw new Exception('No worked schedules found for this period');
      }

      // Get all attendance rules
      $attendanceRules = AttendanceRule::all()->groupBy('code');

      $createdCalculations = [];
      $errors = [];

      $schedulesByWorker = $schedules->groupBy('worker_id');

      foreach ($schedulesByWorker as $workerId => $workerSchedules) {
        try {
          $worker = $workerSchedules->first()->worker;

          // Worker salary and shift info
          $sueldo = (float)($worker->sueldo ?? 0);
          $horasJornada = (float)($worker->horas_jornada ?: 8);

          if ($sueldo == 0 || $horasJornada == 0) {
            $errors[] = "Worker {$worker->nombre_completo}: Invalid salary or shift hours";
            continue;
          }

          // Base hour value
          $valorHoraBase = $sueldo / 30 / $horasJornada;

          // Night surcharge constant (35%)
          $recargoNocturno = 1.35;

          // Create PayrollCalculation
          $calculation = PayrollCalculation::create([
            'period_id' => $periodId,
            'worker_id' => $workerId,
            'company_id' => $worker->company_id ?? null,
            'sede_id' => $worker->sede_id ?? null,
            'salary' => $sueldo,
            'shift_hours' => $horasJornada,
            'base_hour_value' => $valorHoraBase,
            'status' => PayrollCalculation::STATUS_CALCULATED,
            'calculated_at' => now(),
            'calculated_by' => auth()->id(),
          ]);

          $totalEarnings = 0;
          $calculationOrder = 1;

          // Group by code and count days
          $codeGroups = $workerSchedules->groupBy('code');

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

              // If pay = 0, subtract instead of add (make it negative)
              if (!$rule->pay) {
                $total = -$total;
              }

              $totalEarnings += $total;

              // Create detail record
              PayrollCalculationDetail::create([
                'calculation_id' => $calculation->id,
                'concept_id' => null, // For attendance, we don't have a concept_id
                'concept_code' => $code,
                'concept_name' => $rule->description ?? $code,
                'type' => $rule->pay ? 'EARNING' : 'DEDUCTION',
                'category' => 'ATTENDANCE',
                'hour_type' => $rule->hour_type,
                'hours' => $horas,
                'days_worked' => $diasTrabajados,
                'multiplier' => (float)$rule->multiplier,
                'use_shift' => $rule->use_shift,
                'hour_value' => $valorHora,
                'amount' => $total,
                'calculation_order' => $calculationOrder++,
              ]);
            }
          }

          // Update calculation totals
          $calculation->update([
            'total_earnings' => $totalEarnings > 0 ? $totalEarnings : 0,
            'total_deductions' => $totalEarnings < 0 ? abs($totalEarnings) : 0,
            'net_salary' => $totalEarnings,
          ]);

          $createdCalculations[] = $calculation->id;
        } catch (Exception $e) {
          $errors[] = "Worker ID {$workerId}: " . $e->getMessage();
        }
      }

      DB::commit();

      return [
        'success' => true,
        'period_id' => $periodId,
        'calculations_created' => count($createdCalculations),
        'calculation_ids' => $createdCalculations,
        'errors' => $errors,
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Recalculate payroll calculations for a period
   * Deletes existing calculations and regenerates them based on current attendance schedules
   *
   * @param int $periodId
   * @return array
   * @throws Exception
   */
  public function recalculatePayrollCalculations(int $periodId)
  {
    try {
      DB::beginTransaction();

      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }

      // Check if period allows recalculation
      $existingCalculations = PayrollCalculation::where('period_id', $periodId)->get();

      // Don't allow recalculation if any calculation is APPROVED or PAID
      $lockedCalculations = $existingCalculations->filter(function ($calc) {
        return in_array($calc->status, [PayrollCalculation::STATUS_APPROVED, PayrollCalculation::STATUS_PAID]);
      });

      if ($lockedCalculations->count() > 0) {
        throw new Exception("Cannot recalculate: {$lockedCalculations->count()} calculations are already APPROVED or PAID. Please delete them manually first.");
      }

      // Delete existing calculations (this will cascade delete details)
      PayrollCalculation::where('period_id', $periodId)->delete();

      DB::commit();

      // Now generate new calculations
      return $this->generatePayrollCalculations($periodId);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
