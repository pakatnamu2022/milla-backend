<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollCalculationResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollCalculationDetail;
use App\Models\gp\gestionhumana\payroll\PayrollConcept;
use App\Models\gp\gestionhumana\payroll\PayrollFormulaVariable;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
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
   * Calculate payroll for a period
   */
  public function calculatePayroll(array $data)
  {
    try {
      DB::beginTransaction();

      $periodId = $data['period_id'];
      $workerIds = $data['worker_ids'] ?? null;

      // Get period
      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }

      if (!$period->canCalculate()) {
        throw new Exception('Cannot calculate: period is in ' . $period->status . ' status');
      }

      // Update period status to processing
      $period->update(['status' => PayrollPeriod::STATUS_PROCESSING]);

      // Get workers to calculate
      $schedulesQuery = PayrollSchedule::where('period_id', $periodId)
        ->where('status', PayrollSchedule::STATUS_WORKED);

      if ($workerIds) {
        $schedulesQuery->whereIn('worker_id', $workerIds);
      }

      $workerIdsToCalculate = $schedulesQuery->distinct()->pluck('worker_id');

      if ($workerIdsToCalculate->isEmpty()) {
        throw new Exception('No worked schedules found for this period');
      }

      // Get active concepts ordered by calculation order
      $concepts = PayrollConcept::active()->ordered()->get();

      // Get formula variables
      $formulaVariables = PayrollFormulaVariable::active()->fixed()->pluck('value', 'code')->toArray();

      $calculations = [];
      $errors = [];

      foreach ($workerIdsToCalculate as $workerId) {
        try {
          $calculation = $this->calculateWorkerPayroll($period, $workerId, $concepts, $formulaVariables);
          $calculations[] = $calculation;
        } catch (Exception $e) {
          $errors[] = "Worker {$workerId}: " . $e->getMessage();
        }
      }

      // Update period status to calculated
      $period->update(['status' => PayrollPeriod::STATUS_CALCULATED]);

      DB::commit();

      return [
        'period' => new \App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource($period->fresh()),
        'calculations_count' => count($calculations),
        'errors' => $errors,
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Calculate payroll for a single worker
   */
  protected function calculateWorkerPayroll(
    PayrollPeriod $period,
    int $workerId,
    $concepts,
    array $formulaVariables
  ): PayrollCalculation {
    // Get worker
    $worker = Worker::with(['sede'])->find($workerId);
    if (!$worker) {
      throw new Exception("Worker {$workerId} not found");
    }

    // Get worker's schedules for this period
    $schedules = PayrollSchedule::with(['workType'])
      ->where('period_id', $period->id)
      ->where('worker_id', $workerId)
      ->where('status', PayrollSchedule::STATUS_WORKED)
      ->get();

    // Calculate hours summary
    $hoursSummary = $this->calculateHoursSummary($schedules);

    // Get or create calculation
    $calculation = PayrollCalculation::updateOrCreate(
      [
        'period_id' => $period->id,
        'worker_id' => $workerId,
      ],
      [
        'company_id' => $worker->sede->empresa_id ?? null,
        'sede_id' => $worker->sede_id,
        'total_normal_hours' => $hoursSummary['normal_hours'],
        'total_extra_hours_25' => $hoursSummary['extra_hours_25'],
        'total_extra_hours_35' => $hoursSummary['extra_hours_35'],
        'total_night_hours' => $hoursSummary['night_hours'],
        'total_holiday_hours' => $hoursSummary['holiday_hours'],
        'days_worked' => $hoursSummary['days_worked'],
        'days_absent' => $hoursSummary['days_absent'],
        'status' => PayrollCalculation::STATUS_DRAFT,
        'calculated_at' => now(),
        'calculated_by' => auth()->id(),
      ]
    );

    // Delete existing details
    $calculation->details()->delete();

    // Build variables for formula evaluation
    $variables = $this->buildVariables($worker, $hoursSummary, $formulaVariables);

    // Calculate each concept
    $totalEarnings = 0;
    $totalDeductions = 0;
    $employerCost = 0;
    $calculatedValues = [];

    foreach ($concepts as $concept) {
      if (empty($concept->formula)) {
        continue;
      }

      try {
        // Merge calculated values into variables
        $currentVariables = array_merge($variables, $calculatedValues);

        // Evaluate formula
        $amount = $this->formulaParser->evaluate($concept->formula, $currentVariables);

        // Store calculated value for use in subsequent formulas
        $calculatedValues[$concept->code] = $amount;

        // Create detail record
        PayrollCalculationDetail::create([
          'calculation_id' => $calculation->id,
          'concept_id' => $concept->id,
          'concept_code' => $concept->code,
          'concept_name' => $concept->name,
          'type' => $concept->type,
          'formula_used' => $concept->formula,
          'variables_snapshot' => array_intersect_key(
            $currentVariables,
            array_flip($this->formulaParser->extractVariables($concept->formula))
          ),
          'calculated_amount' => $amount,
          'final_amount' => $amount,
          'calculation_order' => $concept->calculation_order,
        ]);

        // Sum by type
        switch ($concept->type) {
          case PayrollConcept::TYPE_EARNING:
            $totalEarnings += $amount;
            break;
          case PayrollConcept::TYPE_DEDUCTION:
            $totalDeductions += $amount;
            break;
          case PayrollConcept::TYPE_EMPLOYER_CONTRIBUTION:
            $employerCost += $amount;
            break;
        }
      } catch (Exception $e) {
        // Log error but continue with other concepts
        \Log::warning("Error calculating concept {$concept->code} for worker {$workerId}: " . $e->getMessage());
      }
    }

    // Update calculation totals
    $grossSalary = $calculatedValues['BASIC_SALARY'] ?? $totalEarnings;
    $netSalary = $totalEarnings - $totalDeductions;

    $calculation->update([
      'gross_salary' => $grossSalary,
      'total_earnings' => $totalEarnings,
      'total_deductions' => $totalDeductions,
      'net_salary' => $netSalary,
      'employer_cost' => $employerCost,
      'status' => PayrollCalculation::STATUS_CALCULATED,
    ]);

    return $calculation;
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
      $hours = (float) $schedule->hours_worked;
      $extraHours = (float) $schedule->extra_hours;

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
    $sueldo = (float) ($worker->sueldo ?? 0);

    // Calculate derived values
    $dailyRate = $sueldo / 30;
    $hourlyRate = $dailyRate / 8;

    return array_merge($formulaVariables, [
      // Worker data
      'SUELDO' => $sueldo,
      'DAILY_RATE' => round($dailyRate, 4),
      'HOURLY_RATE' => round($hourlyRate, 4),

      // Hours worked
      'NORMAL_HOURS' => $hoursSummary['normal_hours'],
      'EXTRA_HOURS_25' => $hoursSummary['extra_hours_25'],
      'EXTRA_HOURS_35' => $hoursSummary['extra_hours_35'],
      'NIGHT_HOURS' => $hoursSummary['night_hours'],
      'HOLIDAY_HOURS' => $hoursSummary['holiday_hours'],

      // Days
      'DAYS_WORKED' => $hoursSummary['days_worked'],
      'DAYS_ABSENT' => $hoursSummary['days_absent'],
    ]);
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
   * Approve all calculations for a period
   */
  public function approveAll(int $periodId)
  {
    try {
      DB::beginTransaction();

      $period = PayrollPeriod::find($periodId);
      if (!$period) {
        throw new Exception('Period not found');
      }

      $calculations = PayrollCalculation::where('period_id', $periodId)
        ->where('status', PayrollCalculation::STATUS_CALCULATED)
        ->get();

      if ($calculations->isEmpty()) {
        throw new Exception('No calculations to approve');
      }

      foreach ($calculations as $calculation) {
        $calculation->update([
          'status' => PayrollCalculation::STATUS_APPROVED,
          'approved_at' => now(),
          'approved_by' => auth()->id(),
        ]);
      }

      // Update period status
      $period->update(['status' => PayrollPeriod::STATUS_APPROVED]);

      DB::commit();

      return [
        'period' => new \App\Http\Resources\gp\gestionhumana\payroll\PayrollPeriodResource($period->fresh()),
        'approved_count' => $calculations->count(),
      ];
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
