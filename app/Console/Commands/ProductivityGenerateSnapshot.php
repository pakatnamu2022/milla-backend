<?php

namespace App\Console\Commands;

use App\Http\Services\ap\postventa\Dashboard\ProductivitySnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProductivityGenerateSnapshot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'productivity:snapshot
                            {--year= : Year for the snapshot (default: previous month)}
                            {--month= : Month for the snapshot (1-12) (default: previous month)}
                            {--sede= : Sede ID (use "all" for all sedes with workshop, or specific ID)}
                            {--months= : Regenerate last N months (1-3, ignores --year and --month)}
                            {--force : Overwrite existing snapshots}
                            {--dry-run : Preview data without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly productivity snapshots for sedes with workshop (has_workshop = 1)';

    protected ProductivitySnapshotService $snapshotService;

    public function __construct(ProductivitySnapshotService $snapshotService)
    {
        parent::__construct();
        $this->snapshotService = $snapshotService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monthsOption = $this->option('months');
        $sedeOption = $this->option('sede');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        // Handle --months option (regenerate last N months)
        if ($monthsOption) {
            $months = (int) $monthsOption;

            if ($months < 1 || $months > 3) {
                $this->error("Invalid --months value: {$months}. Must be between 1 and 3.");
                return Command::FAILURE;
            }

            return $this->regenerateLastMonths($months, $sedeOption, $force);
        }

        // Determine year and month
        $year = $this->option('year');
        $month = $this->option('month');

        // Default to previous month if not specified
        if (!$year || !$month) {
            $previousMonth = Carbon::now()->subMonth();
            $year = $year ?: $previousMonth->year;
            $month = $month ?: $previousMonth->month;

            $this->info("Using previous month: {$previousMonth->format('F Y')}");
        }

        // Validate inputs
        $year = (int) $year;
        $month = (int) $month;

        if ($month < 1 || $month > 12) {
            $this->error("Invalid month: {$month}. Must be between 1 and 12.");
            return Command::FAILURE;
        }

        $periodName = Carbon::create($year, $month, 1)->format('F Y');

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  PRODUCTIVITY SNAPSHOT GENERATOR");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Period: {$periodName}");
        $this->info("Mode: " . ($dryRun ? 'DRY RUN (preview only)' : ($force ? 'FORCE (overwrite)' : 'Normal')));
        $this->newLine();

        try {
            if ($dryRun) {
                return $this->handleDryRun($year, $month, $sedeOption);
            }

            // Handle different sede options
            // Default (no --sede option) or --sede=all → generate for all sedes with workshop
            if ($sedeOption === null || $sedeOption === 'all') {
                return $this->generateAllSnapshots($year, $month, $force);
            } else {
                // Specific sede ID
                $sedeId = (int) $sedeOption;
                return $this->generateSingleSnapshot($year, $month, $sedeId, $force, "Sede {$sedeId}");
            }
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Generate snapshots for all sedes with workshop (has_workshop = 1)
     */
    protected function generateAllSnapshots(int $year, int $month, bool $force): int
    {
        $this->info("Generating snapshots for all sedes with workshop (has_workshop = 1)...");
        $this->newLine();

        $snapshots = $this->snapshotService->generateAllSnapshots($year, $month, $force);

        // Handle error case (no sedes with workshop)
        if (isset($snapshots['error'])) {
            $this->error($snapshots['error']);
            return Command::FAILURE;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($snapshots as $key => $snapshot) {
            if (is_array($snapshot) && isset($snapshot['error'])) {
                $this->error("✗ {$key}: {$snapshot['error']}");
                $errorCount++;
            } else {
                $this->info("✓ {$key}");
                $this->displaySnapshotSummary($snapshot);
                $successCount++;
            }
            $this->newLine();
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Summary: {$successCount} succeeded, {$errorCount} failed");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Generate single snapshot for a specific sede
     */
    protected function generateSingleSnapshot(int $year, int $month, int $sedeId, bool $force, string $label): int
    {
        $this->info("Generating snapshot for: {$label}");
        $this->newLine();

        $snapshot = $this->snapshotService->generateSnapshot($year, $month, $sedeId, $force);

        $this->info("✓ Snapshot generated successfully!");
        $this->displaySnapshotSummary($snapshot);

        return Command::SUCCESS;
    }

    /**
     * Regenerate snapshots for the last N months
     */
    protected function regenerateLastMonths(int $months, ?string $sedeOption, bool $force): int
    {
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  REGENERATING LAST {$months} MONTH(S)");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        $totalSuccess = 0;
        $totalErrors = 0;

        // Iterate from current month backwards
        for ($i = 0; $i < $months; $i++) {
            $targetMonth = Carbon::now()->subMonths($i);
            $year = $targetMonth->year;
            $month = $targetMonth->month;

            $this->info("Processing {$targetMonth->format('F Y')}...");

            try {
                // Always use --sede=all for multi-month regeneration
                $snapshots = $this->snapshotService->generateAllSnapshots($year, $month, true);

                if (isset($snapshots['error'])) {
                    $this->error("✗ {$targetMonth->format('F Y')}: {$snapshots['error']}");
                    $totalErrors++;
                } else {
                    $successCount = 0;
                    $errorCount = 0;

                    foreach ($snapshots as $key => $snapshot) {
                        if (is_array($snapshot) && isset($snapshot['error'])) {
                            $errorCount++;
                        } else {
                            $successCount++;
                        }
                    }

                    $this->info("✓ {$targetMonth->format('F Y')}: {$successCount} sedes updated");
                    $totalSuccess += $successCount;
                    $totalErrors += $errorCount;
                }
            } catch (\Exception $e) {
                $this->error("✗ {$targetMonth->format('F Y')}: {$e->getMessage()}");
                $totalErrors++;
            }

            $this->newLine();
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Final Summary: {$totalSuccess} snapshots updated, {$totalErrors} errors");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return $totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Handle dry run mode
     */
    protected function handleDryRun(int $year, int $month, ?string $sedeOption): int
    {
        $sedeId = null;
        $label = 'First sede with workshop';

        if ($sedeOption && $sedeOption !== 'all') {
            $sedeId = (int) $sedeOption;
            $label = "Sede {$sedeId}";
        }

        $this->warn("DRY RUN MODE - Data will NOT be saved");
        $this->info("Preview for: {$label}");
        $this->newLine();

        $data = $this->snapshotService->previewSnapshot($year, $month, $sedeId);

        // Update label if sede was auto-selected
        if ($sedeOption === null || $sedeOption === 'all') {
            $this->info("Using Sede ID: {$data['sede_id']} (first active sede with workshop)");
            $this->newLine();
        }

        $this->displaySnapshotData($data);

        return Command::SUCCESS;
    }

    /**
     * Display snapshot summary
     */
    protected function displaySnapshotSummary($snapshot): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Technicians', $snapshot->total_technicians],
                ['Billed Hours', number_format($snapshot->total_billed_hours, 2)],
                ['Standard Hours', number_format($snapshot->total_standard_hours, 2)],
                ['Productivity %', number_format($snapshot->average_productivity_percentage, 2) . '%'],
                ['Earnings', 'S/ ' . number_format($snapshot->total_earnings, 2)],
                ['OTs Closed', $snapshot->total_ots_closed],
                ['OTs with Labour', $snapshot->ots_with_labour_charged],
                ['OTs without Labour', $snapshot->ots_without_labour_charged],
                ['Labour Coverage', number_format($snapshot->labour_coverage_rate ?? 0, 2) . '%'],
            ]
        );
    }

    /**
     * Display snapshot data array (for dry-run)
     */
    protected function displaySnapshotData(array $data): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Year', $data['year']],
                ['Month', $data['month']],
                ['Sede ID', $data['sede_id'] ?? 'Consolidated'],
                ['Technicians', $data['total_technicians']],
                ['Billed Hours', number_format($data['total_billed_hours'], 2)],
                ['Standard Hours', number_format($data['total_standard_hours'], 2)],
                ['Productivity Hours', number_format($data['total_productivity_hours'], 2)],
                ['Productivity %', number_format($data['average_productivity_percentage'], 2) . '%'],
                ['Earnings', 'S/ ' . number_format($data['total_earnings'], 2)],
                ['OTs Closed', $data['total_ots_closed']],
                ['OTs with Labour', $data['ots_with_labour_charged']],
                ['OTs without Labour', $data['ots_without_labour_charged']],
                ['Avg Hours per OT', number_format($data['avg_hours_per_ot'] ?? 0, 2)],
                ['Billing Rate', number_format($data['billing_rate'] ?? 0, 2) . '%'],
                ['Reentry Rate', number_format($data['reentry_rate'] ?? 0, 2) . '%'],
                ['Labour Coverage', number_format($data['labour_coverage_rate'] ?? 0, 2) . '%'],
                ['Status: Exceeded', $data['technicians_exceeded']],
                ['Status: On Track', $data['technicians_on_track']],
                ['Status: Warning', $data['technicians_warning']],
                ['Status: Critical', $data['technicians_critical']],
            ]
        );
    }
}