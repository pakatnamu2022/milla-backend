<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollCalculationResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollCalculatorService extends BaseService
{
  protected FormulaParserService $formulaParser;

  public function __construct(FormulaParserService $formulaParser)
  {
    $this->formulaParser = $formulaParser;
  }

  /**
   * Get all calculations with filters and pagination
   */
  public function list(Request $request)
  {
    $query = PayrollCalculation::with(['worker', 'period', 'company', 'sede']);

    return $this->getFilteredResults(
      $query,
      $request,
      PayrollCalculation::filters,
      PayrollCalculation::sorts,
      PayrollCalculationResource::class,
    );
  }

  /**
   * Find a calculation by ID
   */
  public function find($id)
  {
    $calculation = PayrollCalculation::with([
      'worker',
      'period',
      'company',
      'sede',
      'details.concept',
      'calculatedByUser',
      'approvedByUser'
    ])->find($id);

    if (!$calculation) {
      throw new Exception('Calculation not found');
    }
    return $calculation;
  }

  /**
   * Show a calculation by ID
   */
  public function show($id)
  {
    return new PayrollCalculationResource($this->find($id));
  }

  /**
   * Calculate hours summary from schedules
   */
  protected function calculateHoursSummary($schedules): array
  {
    $normalHours = 0;
    $extraHours25 = 0;
    $extraHours35 = 0;
    $nightHours = 0;
    $holidayHours = 0;
    $daysWorked = 0;

    foreach ($schedules as $schedule) {
      $workType = $schedule->workType;
      $hours = (float)$schedule->hours_worked;
      $extraHours = (float)$schedule->extra_hours;

      if ($workType->is_night_shift) {
        $nightHours += $hours;
      } elseif ($workType->is_holiday || $workType->is_sunday) {
        $holidayHours += $hours;
      } else {
        $normalHours += $hours;
      }

      // Extra hours classification (25% or 35%)
      // First 2 extra hours at 25%, rest at 35%
      if ($extraHours > 0) {
        $extraAt25 = min($extraHours, 2);
        $extraAt35 = max(0, $extraHours - 2);
        $extraHours25 += $extraAt25;
        $extraHours35 += $extraAt35;
      }

      $daysWorked++;
    }

    // Calculate absent days (assuming 30-day month)
    $daysAbsent = max(0, 30 - $daysWorked);

    return [
      'normal_hours' => round($normalHours, 2),
      'extra_hours_25' => round($extraHours25, 2),
      'extra_hours_35' => round($extraHours35, 2),
      'night_hours' => round($nightHours, 2),
      'holiday_hours' => round($holidayHours, 2),
      'days_worked' => $daysWorked,
      'days_absent' => $daysAbsent,
    ];
  }

  /**
   * Build variables for formula evaluation
   */
  protected function buildVariables(Worker $worker, array $hoursSummary, array $formulaVariables): array
  {
    // Get base salary from worker (using existing 'sueldo' field)
    $sueldo = (float)($worker->sueldo ?? 0);

    // Calculate derived values
    $dailyRate = $sueldo / 30;
    $hourlyRate = $dailyRate / 8;

    return array_merge($formulaVariables, [
      // Worker data
      'SUELDO' => $sueldo,
      'DAILY_RATE' => round($dailyRate, 4),
      'HOURLY_RATE' => round($hourlyRate, 4),

      // Hours worked
      'NORMAL_HOURS' => $hoursSummary['normal_hours'] ?? 0,
      'EXTRA_HOURS_25' => $hoursSummary['extra_hours_25'] ?? 0,
      'EXTRA_HOURS_35' => $hoursSummary['extra_hours_35'] ?? 0,
      'NIGHT_HOURS' => $hoursSummary['night_hours'] ?? 0,
      'HOLIDAY_HOURS' => $hoursSummary['holiday_hours'] ?? 0,

      // Days
      'DAYS_WORKED' => $hoursSummary['days_worked'] ?? 0,
      'DAYS_ABSENT' => $hoursSummary['days_absent'] ?? 0,
    ]);
  }

  /**
   * Calculate hours using segment-based approach
   *
   * @param \Illuminate\Support\Collection $schedules Worker's schedules for the period
   * @return array Segmented hours breakdown
   */
  protected function calculateSegmentedHours($schedules): array
  {
    $result = [
      'by_work_type' => [], // Detailed breakdown per work type
      'total_worked_hours' => 0,
      'total_break_hours' => 0,
      'days_worked' => 0,
    ];

    foreach ($schedules as $schedule) {
      $workType = $schedule->workType;
      $hoursWorked = (float)$schedule->hours_worked;
      $extraHours = (float)$schedule->extra_hours;
      $totalHours = $hoursWorked + $extraHours;

      // Initialize work type data if not exists
      if (!isset($result['by_work_type'][$workType->code])) {
        $result['by_work_type'][$workType->code] = [
          'total_hours' => 0,
          'break_hours' => 0,
          'net_hours' => 0,
          'nocturnal_base' => (float)$workType->nocturnal_base_multiplier,
          'segments' => [],
        ];
      }

      // Get segments ordered
      $segments = $workType->segments;

      if ($segments->isEmpty()) {
        // Legacy mode: no segments defined, use simple multiplier
        $result['by_work_type'][$workType->code]['total_hours'] += $totalHours;
        $result['by_work_type'][$workType->code]['net_hours'] += $totalHours;
        $result['total_worked_hours'] += $totalHours;
        $result['days_worked']++;
        continue;
      }

      // Segment-based calculation
      $remainingHours = $totalHours;
      $dailyBreakHours = 0;

      foreach ($segments as $segment) {
        if ($remainingHours <= 0 && $segment->isWork()) {
          break; // No more hours to allocate for work segments
        }

        if ($segment->isWork()) {
          $segmentDuration = min($remainingHours, (float)$segment->duration_hours);

          // Calculate effective multiplier (nocturnal base × segment multiplier)
          $effectiveMultiplier = $workType->nocturnal_base_multiplier * $segment->multiplier;

          $result['by_work_type'][$workType->code]['segments'][] = [
            'order' => $segment->segment_order,
            'type' => 'WORK',
            'duration' => $segmentDuration,
            'segment_multiplier' => (float)$segment->multiplier,
            'nocturnal_base' => (float)$workType->nocturnal_base_multiplier,
            'effective_multiplier' => $effectiveMultiplier,
            'description' => $segment->description,
          ];

          $remainingHours -= $segmentDuration;

        } elseif ($segment->isBreak()) {
          // Break deduction
          $breakDuration = (float)$segment->duration_hours;
          $dailyBreakHours += $breakDuration;

          $result['by_work_type'][$workType->code]['segments'][] = [
            'order' => $segment->segment_order,
            'type' => 'BREAK',
            'duration' => $breakDuration,
            'deduction_hours' => $breakDuration,
            'description' => $segment->description,
          ];
        }
      }

      // Handle extra hours beyond configured segments
      if ($remainingHours > 0) {
        // Extra hours beyond shift use the last work segment's multiplier
        $workSegments = collect($result['by_work_type'][$workType->code]['segments'])
          ->where('type', 'WORK');

        $lastWorkSegment = $workSegments->last();
        $extraMultiplier = $lastWorkSegment['effective_multiplier'] ?? 1.35;

        $result['by_work_type'][$workType->code]['segments'][] = [
          'order' => 999,
          'type' => 'EXTRA',
          'duration' => $remainingHours,
          'effective_multiplier' => $extraMultiplier,
          'description' => 'Extra hours beyond configured segments',
        ];
      }

      // Update totals for this work type
      $result['by_work_type'][$workType->code]['total_hours'] += $totalHours;
      $result['by_work_type'][$workType->code]['break_hours'] += $dailyBreakHours;
      $result['by_work_type'][$workType->code]['net_hours'] += ($totalHours - $dailyBreakHours);

      $result['total_worked_hours'] += ($totalHours - $dailyBreakHours);
      $result['total_break_hours'] += $dailyBreakHours;
      $result['days_worked']++;
    }

    $result['days_absent'] = max(0, 30 - $result['days_worked']);

    return $result;
  }

  /**
   * Calculate earnings from segmented hours breakdown
   *
   * @param array $segmentedHours Result from calculateSegmentedHours()
   * @param float $hourlyRate Worker's hourly rate
   * @return array Earnings breakdown and variables for formulas
   */
  protected function calculateSegmentedEarnings(array $segmentedHours, float $hourlyRate): array
  {
    $earnings = [];
    $variables = [];
    $totalEarnings = 0;

    foreach ($segmentedHours['by_work_type'] as $workTypeCode => $workTypeData) {
      if (empty($workTypeData['segments'])) {
        // Legacy calculation - should not happen with current seeder
        continue;
      }

      // Segment-based calculation
      $workTypeTotal = 0;

      foreach ($workTypeData['segments'] as $segment) {
        if ($segment['type'] === 'WORK' || $segment['type'] === 'EXTRA') {
          $segmentEarning = $segment['duration'] * $hourlyRate * $segment['effective_multiplier'];
          $workTypeTotal += $segmentEarning;
        } elseif ($segment['type'] === 'BREAK') {
          // Break deduction
          $breakDeduction = $segment['duration'] * $hourlyRate;
          $workTypeTotal -= $breakDeduction;
        }
      }

      $earnings[$workTypeCode] = $workTypeTotal;
      $variables["{$workTypeCode}_HOURS"] = $workTypeData['net_hours'];
      $variables["{$workTypeCode}_EARNINGS"] = round($workTypeTotal, 2);
      $totalEarnings += $workTypeTotal;
    }

    $variables['SEGMENTED_TOTAL_EARNINGS'] = round($totalEarnings, 2);
    $variables['TOTAL_WORKED_HOURS'] = $segmentedHours['total_worked_hours'];
    $variables['TOTAL_BREAK_HOURS'] = $segmentedHours['total_break_hours'];

    return [
      'earnings' => $earnings,
      'variables' => $variables,
      'total' => $totalEarnings,
    ];
  }

  /**
   * Approve a calculation
   */
  public function approve(int $id)
  {
    try {
      DB::beginTransaction();

      $calculation = $this->find($id);

      if (!$calculation->canApprove()) {
        throw new Exception('Cannot approve: calculation is in ' . $calculation->status . ' status');
      }

      $calculation->update([
        'status' => PayrollCalculation::STATUS_APPROVED,
        'approved_at' => now(),
        'approved_by' => auth()->id(),
      ]);

      DB::commit();
      return new PayrollCalculationResource($calculation->fresh()->load([
        'worker', 'period', 'company', 'sede', 'details.concept'
      ]));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get calculation summary for a period
   */
  public function getPeriodSummary(int $periodId)
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    $calculations = PayrollCalculation::where('period_id', $periodId)->get();

    return [
      'period' => new \App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource($period),
      'total_workers' => $calculations->count(),
      'total_earnings' => round($calculations->sum('total_earnings'), 2),
      'total_deductions' => round($calculations->sum('total_deductions'), 2),
      'total_net_salary' => round($calculations->sum('net_salary'), 2),
      'total_employer_cost' => round($calculations->sum('employer_cost'), 2),
      'by_status' => $calculations->groupBy('status')->map(fn($group) => $group->count()),
    ];
  }
}
