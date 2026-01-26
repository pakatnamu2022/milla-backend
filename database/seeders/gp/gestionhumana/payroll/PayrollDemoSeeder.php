<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Http\Services\gp\gestionhumana\payroll\FormulaParserService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollCalculatorService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollPeriodService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollScheduleService;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use App\Models\gp\gestionhumana\payroll\PayrollSchedule;
use App\Models\gp\gestionhumana\payroll\PayrollWorkType;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Demo seeder that executes the complete payroll process using services
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollDemoSeeder"
 */
class PayrollDemoSeeder extends Seeder
{
  protected PayrollPeriodService $periodService;
  protected PayrollScheduleService $scheduleService;
  protected PayrollCalculatorService $calculatorService;

  public function __construct()
  {
    $this->periodService = app(PayrollPeriodService::class);
    $this->scheduleService = app(PayrollScheduleService::class);
    $this->calculatorService = new PayrollCalculatorService(new FormulaParserService());
  }

  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->command->info('=== PAYROLL DEMO SEEDER ===');
    $this->command->newLine();

    // Step 1: Create or get period for current month
    $period = $this->createPeriod();
    if (!$period) {
      $this->command->error('Could not create/find period. Aborting.');
      return;
    }

    // Step 2: Get workers with salary
    $workers = $this->getWorkersWithSalary();
    if ($workers->isEmpty()) {
      $this->command->error('No workers with salary (sueldo) found. Aborting.');
      return;
    }

    // Step 3: Create schedules for workers
    $this->createSchedules($period, $workers);

    // Step 4: Calculate payroll
    $this->calculatePayroll($period);

    // Step 5: Show summary
    $this->showSummary($period);

    $this->command->newLine();
    $this->command->info('=== DEMO COMPLETED ===');
  }

  /**
   * Create or get period for current month
   */
  protected function createPeriod(): ?PayrollPeriod
  {
    $year = Carbon::now()->year;
    $month = Carbon::now()->month;

    $this->command->info("Step 1: Creating period for {$year}-{$month}...");

    // Check if period already exists
    $existingPeriod = PayrollPeriod::where('year', $year)
      ->where('month', $month)
      ->first();

    if ($existingPeriod) {
      $this->command->warn("  Period already exists: {$existingPeriod->code}");

      // Reset to OPEN status if needed for demo
      if ($existingPeriod->status !== PayrollPeriod::STATUS_OPEN) {
        $existingPeriod->update(['status' => PayrollPeriod::STATUS_OPEN]);
        $this->command->info("  Reset period status to OPEN for demo");
      }

      // Delete existing calculations and schedules for fresh demo
      $existingPeriod->calculations()->delete();
      $existingPeriod->schedules()->delete();
      $this->command->info("  Cleared existing schedules and calculations");

      return $existingPeriod;
    }

    try {
      $result = $this->periodService->store([
        'year' => $year,
        'month' => $month,
        'payment_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
      ]);

      $period = PayrollPeriod::find($result->id);
      $this->command->info("  Created period: {$period->code}");
      return $period;
    } catch (\Exception $e) {
      $this->command->error("  Error creating period: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Get workers that have a salary defined
   */
  protected function getWorkersWithSalary()
  {
    $this->command->info("Step 2: Finding workers with salary...");

    $workers = Worker::whereNotNull('sueldo')
      ->where('sueldo', '>', 0)
      ->limit(5) // Limit to 5 workers for demo
      ->get();

    $this->command->info("  Found {$workers->count()} workers with salary");

    foreach ($workers as $worker) {
      $this->command->line("    - {$worker->nombre_completo}: S/ " . number_format($worker->sueldo, 2));
    }

    return $workers;
  }

  /**
   * Create schedules for workers
   */
  protected function createSchedules(PayrollPeriod $period, $workers): void
  {
    $this->command->info("Step 3: Creating schedules for workers...");

    // Get work types
    $dayShift = PayrollWorkType::where('code', 'DT')->first();
    $nightShift = PayrollWorkType::where('code', 'NT')->first();

    if (!$dayShift) {
      $this->command->error("  Work type 'DT' not found. Run PayrollWorkTypeSeeder first.");
      return;
    }

    $startDate = Carbon::parse($period->start_date);
    $endDate = Carbon::parse($period->end_date);
    $schedules = [];

    foreach ($workers as $worker) {
      $currentDate = $startDate->copy();
      $isNightWorker = rand(0, 1) === 1; // Randomly assign night shift to some workers

      while ($currentDate->lte($endDate)) {
        // Skip Sundays for this demo
        if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
          $workType = $isNightWorker && $nightShift ? $nightShift : $dayShift;
          $extraHours = rand(0, 10) > 7 ? rand(1, 3) : 0; // 30% chance of extra hours

          $schedules[] = [
            'worker_id' => $worker->id,
            'work_type_id' => $workType->id,
            'work_date' => $currentDate->format('Y-m-d'),
            'hours_worked' => $workType->base_hours,
            'extra_hours' => $extraHours,
            'status' => PayrollSchedule::STATUS_WORKED,
          ];
        }

        $currentDate->addDay();
      }
    }

    try {
      $result = $this->scheduleService->storeBulk([
        'period_id' => $period->id,
        'schedules' => $schedules,
      ]);

      $this->command->info("  Created {$result['created_count']} schedules");

      if (!empty($result['errors'])) {
        foreach ($result['errors'] as $error) {
          $this->command->warn("  Warning: {$error}");
        }
      }
    } catch (\Exception $e) {
      $this->command->error("  Error creating schedules: " . $e->getMessage());
    }
  }

  /**
   * Calculate payroll for the period
   */
  protected function calculatePayroll(PayrollPeriod $period): void
  {
    $this->command->info("Step 4: Calculating payroll...");

    try {
      $result = $this->calculatorService->calculatePayroll([
        'period_id' => $period->id,
      ]);

      $this->command->info("  Calculated payroll for {$result['calculations_count']} workers");

      if (!empty($result['errors'])) {
        foreach ($result['errors'] as $error) {
          $this->command->warn("  Warning: {$error}");
        }
      }
    } catch (\Exception $e) {
      $this->command->error("  Error calculating payroll: " . $e->getMessage());
      Log::error("PayrollDemoSeeder calculation error: " . $e->getMessage());
    }
  }

  /**
   * Show payroll summary
   */
  protected function showSummary(PayrollPeriod $period): void
  {
    $this->command->info("Step 5: Payroll Summary");
    $this->command->newLine();

    try {
      $summary = $this->calculatorService->getPeriodSummary($period->id);

      $this->command->table(
        ['Metric', 'Value'],
        [
          ['Period', $summary['period']->code ?? $period->code],
          ['Total Workers', $summary['total_workers']],
          ['Total Earnings', 'S/ ' . number_format($summary['total_earnings'], 2)],
          ['Total Deductions', 'S/ ' . number_format($summary['total_deductions'], 2)],
          ['Total Net Salary', 'S/ ' . number_format($summary['total_net_salary'], 2)],
          ['Total Employer Cost', 'S/ ' . number_format($summary['total_employer_cost'], 2)],
        ]
      );

      // Show individual calculations
      $this->command->newLine();
      $this->command->info("Individual Calculations:");

      $calculations = $period->calculations()->with('worker')->get();

      $tableData = [];
      foreach ($calculations as $calc) {
        $tableData[] = [
          $calc->worker->nombre_completo ?? 'N/A',
          $calc->days_worked,
          'S/ ' . number_format($calc->total_earnings, 2),
          'S/ ' . number_format($calc->total_deductions, 2),
          'S/ ' . number_format($calc->net_salary, 2),
          $calc->status,
        ];
      }

      $this->command->table(
        ['Worker', 'Days', 'Earnings', 'Deductions', 'Net Salary', 'Status'],
        $tableData
      );
    } catch (\Exception $e) {
      $this->command->error("  Error showing summary: " . $e->getMessage());
    }
  }
}
