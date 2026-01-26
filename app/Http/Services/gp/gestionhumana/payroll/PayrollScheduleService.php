<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollScheduleResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\payroll\PayrollWorkType;
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
    $query = PayrollSchedule::with(['worker', 'workType', 'period']);

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
    $schedule = PayrollSchedule::with(['worker', 'workType', 'period'])->find($id);
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

      // Validate work type exists
      $workType = PayrollWorkType::find($data['work_type_id']);
      if (!$workType) {
        throw new Exception('Work type not found');
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
        'work_type_id' => $data['work_type_id'],
        'period_id' => $data['period_id'],
        'work_date' => $data['work_date'],
        'hours_worked' => $data['hours_worked'] ?? $workType->base_hours,
        'extra_hours' => $data['extra_hours'] ?? 0,
        'notes' => $data['notes'] ?? null,
        'status' => $data['status'] ?? PayrollSchedule::STATUS_SCHEDULED,
      ]);

      DB::commit();
      return new PayrollScheduleResource($schedule->load(['worker', 'workType', 'period']));
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

          // Validate work type exists
          $workType = PayrollWorkType::find($scheduleData['work_type_id']);
          if (!$workType) {
            $errors[] = "Row {$index}: Work type not found";
            continue;
          }

          // Check for duplicate
          $existingSchedule = PayrollSchedule::where('worker_id', $scheduleData['worker_id'])
            ->where('work_date', $scheduleData['work_date'])
            ->first();

          if ($existingSchedule) {
            // Update existing schedule
            $existingSchedule->update([
              'work_type_id' => $scheduleData['work_type_id'],
              'hours_worked' => $scheduleData['hours_worked'] ?? $workType->base_hours,
              'extra_hours' => $scheduleData['extra_hours'] ?? 0,
              'notes' => $scheduleData['notes'] ?? null,
              'status' => $scheduleData['status'] ?? PayrollSchedule::STATUS_SCHEDULED,
            ]);
            $createdSchedules[] = $existingSchedule;
          } else {
            // Create new schedule
            $schedule = PayrollSchedule::create([
              'worker_id' => $scheduleData['worker_id'],
              'work_type_id' => $scheduleData['work_type_id'],
              'period_id' => $periodId,
              'work_date' => $scheduleData['work_date'],
              'hours_worked' => $scheduleData['hours_worked'] ?? $workType->base_hours,
              'extra_hours' => $scheduleData['extra_hours'] ?? 0,
              'notes' => $scheduleData['notes'] ?? null,
              'status' => $scheduleData['status'] ?? PayrollSchedule::STATUS_SCHEDULED,
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
          collect($createdSchedules)->map(fn($s) => $s->load(['worker', 'workType', 'period']))
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

      // Validate work type if changing
      if (isset($data['work_type_id']) && $data['work_type_id'] !== $schedule->work_type_id) {
        $workType = PayrollWorkType::find($data['work_type_id']);
        if (!$workType) {
          throw new Exception('Work type not found');
        }
      }

      $schedule->update([
        'work_type_id' => $data['work_type_id'] ?? $schedule->work_type_id,
        'hours_worked' => $data['hours_worked'] ?? $schedule->hours_worked,
        'extra_hours' => $data['extra_hours'] ?? $schedule->extra_hours,
        'notes' => $data['notes'] ?? $schedule->notes,
        'status' => $data['status'] ?? $schedule->status,
      ]);

      DB::commit();
      return new PayrollScheduleResource($schedule->fresh()->load(['worker', 'workType', 'period']));
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
   * Get summary of hours by period
   */
  public function getSummaryByPeriod(int $periodId)
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    $schedules = PayrollSchedule::with(['worker', 'workType'])
      ->where('period_id', $periodId)
      ->where('status', PayrollSchedule::STATUS_WORKED)
      ->get();

    $summary = $schedules->groupBy('worker_id')->map(function ($workerSchedules) {
      $worker = $workerSchedules->first()->worker;

      $totalNormalHours = 0;
      $totalExtraHours = 0;
      $totalNightHours = 0;
      $totalHolidayHours = 0;
      $daysWorked = 0;

      foreach ($workerSchedules as $schedule) {
        $workType = $schedule->workType;
        $hours = (float) $schedule->hours_worked;

        if ($workType->is_night_shift) {
          $totalNightHours += $hours;
        } elseif ($workType->is_holiday || $workType->is_sunday) {
          $totalHolidayHours += $hours;
        } else {
          $totalNormalHours += $hours;
        }

        $totalExtraHours += (float) $schedule->extra_hours;
        $daysWorked++;
      }

      return [
        'worker_id' => $worker->id,
        'worker_name' => $worker->nombre_completo,
        'total_normal_hours' => round($totalNormalHours, 2),
        'total_extra_hours' => round($totalExtraHours, 2),
        'total_night_hours' => round($totalNightHours, 2),
        'total_holiday_hours' => round($totalHolidayHours, 2),
        'days_worked' => $daysWorked,
      ];
    })->values();

    return [
      'period' => new \App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource($period),
      'workers_count' => $summary->count(),
      'summary' => $summary,
    ];
  }
}
