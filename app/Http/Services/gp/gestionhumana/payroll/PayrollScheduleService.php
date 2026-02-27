<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollScheduleResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\AttendanceRule;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
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

      $schedule = PayrollSchedule::create([
        'worker_id' => $data['worker_id'],
        'code' => $data['code'],
        'period_id' => $data['period_id'],
        'work_date' => $data['work_date'],
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

          if ($existingSchedule) {
            // Update existing schedule
            $existingSchedule->update([
              'code' => $scheduleData['code'],
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

      $schedule->update([
        'code' => $data['code'] ?? $schedule->code,
        'notes' => $data['notes'] ?? $schedule->notes,
        'status' => $data['status'] ?? $schedule->status,
      ]);

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
      $horasJornada = (float)($worker->horas_jornada ?? 8);

      if ($sueldo == 0 || $horasJornada == 0) {
        throw new Exception("Worker {$worker->nombre_completo} has invalid salary or shift hours");
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
    })->values();

    return [
      'period' => new \App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource($period),
      'workers_count' => $summary->count(),
      'summary' => $summary,
    ];
  }
}
