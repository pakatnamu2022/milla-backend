<?php

namespace App\Http\Services\ap\postventa\Dashboard;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\ProductivityMonthlySnapshot;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductivitySnapshotService
{
    protected ProductivityDashboardService $dashboardService;

    public function __construct(ProductivityDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Generate monthly snapshot for a specific period
     *
     * @param int $year
     * @param int $month
     * @param int $sedeId Sede ID (required - must have has_workshop = 1)
     * @param bool $force Overwrite if exists
     * @return ProductivityMonthlySnapshot
     * @throws \Exception
     */
    public function generateSnapshot(int $year, int $month, int $sedeId, bool $force = false): ProductivityMonthlySnapshot
    {
        // Validate month
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException("Month must be between 1 and 12");
        }

        // Validate sede has workshop
        $sede = DB::table('config_sede')
            ->where('id', $sedeId)
            ->where('has_workshop', 1)
            ->first();

        if (!$sede) {
            throw new \InvalidArgumentException("Sede {$sedeId} does not exist or does not have a workshop (has_workshop = 1)");
        }

        // Check if snapshot already exists
        $existing = ProductivityMonthlySnapshot::where('year', $year)
            ->where('month', $month)
            ->where('sede_id', $sedeId)
            ->first();

        if ($existing && !$force) {
            throw new \Exception("Snapshot already exists for this period. Use --force to overwrite.");
        }

        // Calculate date range for the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get productivity data from existing dashboard service
        $productivityData = $this->dashboardService->getDashboardData(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $sedeId,
            false // Don't use cache
        );

        // Get work order metrics
        $otMetrics = $this->calculateWorkOrderMetrics($year, $month, $sedeId);

        // Prepare snapshot data
        $snapshotData = $this->prepareSnapshotData($productivityData, $otMetrics, $year, $month, $sedeId);

        // Save or update snapshot
        if ($existing) {
            $existing->update($snapshotData);
            $snapshot = $existing;
            Log::info("Productivity snapshot regenerated", [
                'year' => $year,
                'month' => $month,
                'sede_id' => $sedeId
            ]);
        } else {
            $snapshot = ProductivityMonthlySnapshot::create($snapshotData);
            Log::info("Productivity snapshot created", [
                'year' => $year,
                'month' => $month,
                'sede_id' => $sedeId
            ]);
        }

        return $snapshot;
    }

    /**
     * Generate snapshots for all sedes with workshop (has_workshop = 1)
     *
     * @param int $year
     * @param int $month
     * @param bool $force
     * @return array Collection of snapshots
     */
    public function generateAllSnapshots(int $year, int $month, bool $force = false): array
    {
        $snapshots = [];

        // Get all active sedes with workshop (has_workshop = 1)
        $sedes = DB::table('config_sede')
            ->where('status', 1)
            ->where('has_workshop', 1)
            ->pluck('id');

        if ($sedes->isEmpty()) {
            Log::warning("No sedes with workshop found", [
                'year' => $year,
                'month' => $month,
            ]);
            return ['error' => 'No sedes with workshop (has_workshop = 1) found'];
        }

        // Generate snapshot for each sede with workshop
        foreach ($sedes as $sedeId) {
            try {
                $snapshots["sede_{$sedeId}"] = $this->generateSnapshot($year, $month, $sedeId, $force);
            } catch (\Exception $e) {
                Log::error("Error generating sede snapshot", [
                    'year' => $year,
                    'month' => $month,
                    'sede_id' => $sedeId,
                    'error' => $e->getMessage()
                ]);
                $snapshots["sede_{$sedeId}"] = ['error' => $e->getMessage()];
            }
        }

        return $snapshots;
    }

    /**
     * Calculate work order metrics for a specific period
     */
    protected function calculateWorkOrderMetrics(int $year, int $month, int $sedeId): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $closedStatuses = [
            ApMasters::FINISHED_WORK_ORDER_ID,
            ApMasters::CLOSED_WORK_ORDER_ID
        ];

        // Base query for closed work orders in the period
        $baseQuery = ApWorkOrder::query()
            ->whereIn('status_id', $closedStatuses)
            ->whereBetween('official_closing_date', [$startDate, $endDate])
            ->where('sede_id', $sedeId);

        // Total OTs closed
        $totalOtsClosed = $baseQuery->count();

        // OTs with labour charges
        $otsWithLabour = (clone $baseQuery)
            ->whereHas('labours')
            ->count();

        // OTs without labour charges
        $otsWithoutLabour = $totalOtsClosed - $otsWithLabour;

        // Calculate average hours per OT (using billed hours from productivity data)
        // Note: We'll update this from the productivity data, not from here
        $avgHoursPerOt = 0;

        // Calculate labour coverage rate
        $labourCoverageRate = $totalOtsClosed > 0
            ? round(($otsWithLabour / $totalOtsClosed) * 100, 2)
            : 0;

        return [
            'total_ots_closed' => $totalOtsClosed,
            'ots_with_labour_charged' => $otsWithLabour,
            'ots_without_labour_charged' => $otsWithoutLabour,
            'avg_hours_per_ot' => $avgHoursPerOt,
            'labour_coverage_rate' => $labourCoverageRate,
        ];
    }

    /**
     * Prepare snapshot data from productivity and OT metrics
     */
    protected function prepareSnapshotData(array $productivityData, array $otMetrics, int $year, int $month, int $sedeId): array
    {
        $executiveSummary = $productivityData['executive_summary'] ?? [];

        // Calculate reentry rate if data is available
        $reentryRate = null;
        if (!empty($productivityData['technician_detail'])) {
            $totalBilled = array_sum(array_column($productivityData['technician_detail'], 'billed_hours'));
            $totalReentry = array_sum(array_column($productivityData['technician_detail'], 'reentry_hours'));
            $reentryRate = $totalBilled > 0 ? round(($totalReentry / $totalBilled) * 100, 2) : 0;
        }

        // Calculate billing rate
        $totalStandard = $executiveSummary['total_standard_hours'] ?? 0;
        $totalBilled = $executiveSummary['total_billed_hours'] ?? 0;
        $billingRate = $totalStandard > 0
            ? round(($totalBilled / $totalStandard) * 100, 2)
            : 0;

        // Calculate average hours per OT (from billed hours / OTs closed)
        $avgHoursPerOt = $otMetrics['total_ots_closed'] > 0
            ? round($totalBilled / $otMetrics['total_ots_closed'], 2)
            : 0;

        return [
            'year' => $year,
            'month' => $month,
            'sede_id' => $sedeId,

            // Core metrics from productivity dashboard
            'total_technicians' => $executiveSummary['total_technicians'] ?? 0,
            'total_billed_hours' => $executiveSummary['total_billed_hours'] ?? 0,
            'total_standard_hours' => $executiveSummary['total_standard_hours'] ?? 0,
            'total_productivity_hours' => $executiveSummary['total_productivity_hours'] ?? 0,
            'total_earnings' => $executiveSummary['total_earnings'] ?? 0,
            'average_productivity_percentage' => $executiveSummary['average_productivity_percentage'] ?? 0,

            // Work order metrics
            'total_ots_closed' => $otMetrics['total_ots_closed'],
            'ots_with_labour_charged' => $otMetrics['ots_with_labour_charged'],
            'ots_without_labour_charged' => $otMetrics['ots_without_labour_charged'],

            // Efficiency indicators
            'avg_hours_per_ot' => $avgHoursPerOt,
            'billing_rate' => $billingRate,
            'reentry_rate' => $reentryRate,
            'labour_coverage_rate' => $otMetrics['labour_coverage_rate'],

            // Status distribution
            'technicians_exceeded' => $executiveSummary['status_breakdown']['exceeded'] ?? 0,
            'technicians_on_track' => $executiveSummary['status_breakdown']['on_track'] ?? 0,
            'technicians_warning' => $executiveSummary['status_breakdown']['warning'] ?? 0,
            'technicians_critical' => $executiveSummary['status_breakdown']['critical'] ?? 0,

            // Metadata
            'snapshot_date' => now(),
            'created_by' => auth()->id(),
        ];
    }

    /**
     * Preview snapshot data without saving (for dry-run)
     * If sedeId is not provided, uses first active sede with workshop
     */
    public function previewSnapshot(int $year, int $month, ?int $sedeId = null): array
    {
        // If no sede provided, get first active sede with workshop
        if ($sedeId === null) {
            $sedeId = DB::table('config_sede')
                ->where('status', 1)
                ->where('has_workshop', 1)
                ->value('id');

            if (!$sedeId) {
                throw new \Exception("No active sedes with workshop (has_workshop = 1) found for preview");
            }
        }

        // Validate sede has workshop
        $sede = DB::table('config_sede')
            ->where('id', $sedeId)
            ->where('has_workshop', 1)
            ->first();

        if (!$sede) {
            throw new \InvalidArgumentException("Sede {$sedeId} does not exist or does not have a workshop (has_workshop = 1)");
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $productivityData = $this->dashboardService->getDashboardData(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $sedeId,
            false
        );

        $otMetrics = $this->calculateWorkOrderMetrics($year, $month, $sedeId);

        return $this->prepareSnapshotData($productivityData, $otMetrics, $year, $month, $sedeId);
    }
}