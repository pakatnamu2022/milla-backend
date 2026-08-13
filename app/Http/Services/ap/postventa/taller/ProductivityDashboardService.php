<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Models\GeneralMaster;
use App\Models\ap\postventa\taller\ApWorkOrderPlanning;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProductivityDashboardService
{
  /**
   * Get productivity dashboard data for technicians
   *
   * @param string $startDate Format: Y-m-d
   * @param string $endDate Format: Y-m-d
   * @param int|null $sedeId If null, returns all headquarters
   * @param bool $useCache
   * @return array
   */
  public function getDashboardData(string $startDate, string $endDate, ?int $sedeId = null, bool $useCache = true): array
  {
    $cacheKey = "productivity_dashboard_{$startDate}_{$endDate}_" . ($sedeId ?? 'all');

    if (!$useCache) {
      Cache::forget($cacheKey);
    }

    return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($startDate, $endDate, $sedeId) {
      // Get period info
      $period = $this->getPeriodInfo($startDate, $endDate);

      // Get user sede IDs
      $userSedeIds = $this->getUserSedeIds();

      // Build filters for billed hours
      $filters = [
        [
          'column' => 'actual_end_datetime',
          'operator' => 'date_between',
          'value' => [$startDate, $endDate],
        ]
      ];

      if ($sedeId) {
        $filters[] = [
          'column' => 'sede_id',
          'operator' => '=',
          'value' => $sedeId,
        ];
      }

      // Get billed hours by technician (from WorkedHoursBySedeReportService logic)
      $billedData = $this->getBilledHoursByTechnician($filters, $userSedeIds);

      if ($billedData->isEmpty()) {
        return [
          'period' => $period,
          'executive_summary' => $this->getEmptyExecutiveSummary(),
          'headquarters_summary' => [],
          'technician_detail' => [],
          'chart_data' => ['labels' => [], 'datasets' => []]
        ];
      }

      // Get configurations from GeneralMaster
      $workingHoursPerDay = $this->getWorkingHoursPerDay();
      $earningsPerHour = $this->getEarningsPerHour();

      // Calculate technician details with productivity
      $technicianDetail = $this->calculateTechnicianDetail(
        $billedData,
        $period,
        $workingHoursPerDay,
        $earningsPerHour
      );

      // Calculate headquarters summary
      $headquartersSummary = $this->calculateHeadquartersSummary($technicianDetail);

      // Calculate executive summary
      $executiveSummary = $this->calculateExecutiveSummary($technicianDetail, $headquartersSummary);

      // Generate chart data
      $chartData = $this->generateChartData($technicianDetail);

      return [
        'period' => $period,
        'configurations' => [
          'working_hours_per_day' => $workingHoursPerDay,
          'earnings_per_hour' => $earningsPerHour
        ],
        'executive_summary' => $executiveSummary,
        'headquarters_summary' => $headquartersSummary,
        'technician_detail' => $technicianDetail,
        'chart_data' => $chartData
      ];
    });
  }

  /**
   * Get period information
   */
  private function getPeriodInfo(string $startDate, string $endDate): array
  {
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);
    $currentDate = Carbon::now();

    $totalDays = $start->diffInDays($end) + 1;

    // Calculate working days (Monday to Saturday, excluding Sundays)
    $workingDays = 0;
    $current = $start->copy();
    while ($current->lte($end)) {
      // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
      if ($current->dayOfWeek !== 0) { // Exclude Sunday
        $workingDays++;
      }
      $current->addDay();
    }

    return [
      'start_date' => $startDate,
      'end_date' => $endDate,
      'current_date' => $currentDate->format('Y-m-d'),
      'total_days' => $totalDays,
      'working_days' => $workingDays,
      'description' => $start->translatedFormat('d/m/Y') . ' - ' . $end->translatedFormat('d/m/Y')
    ];
  }

  /**
   * Get working hours per day from GeneralMaster
   */
  private function getWorkingHoursPerDay(): float
  {
    $config = GeneralMaster::find(GeneralMaster::WORKING_HOURS_ID);
    return $config ? (float)$config->value : 8.0;
  }

  /**
   * Get earnings per productivity hour (hardcoded for now)
   * TODO: Move to GeneralMaster
   */
  private function getEarningsPerHour(): float
  {
    // Hardcoded as 8 soles per hour
    return 8.0;
  }

  /**
   * Get billed hours by technician
   * Adapted from WorkedHoursBySedeReportService::getBilledHoursBySedeReport
   */
  private function getBilledHoursByTechnician(array $filters, array $userSedeIds): \Illuminate\Support\Collection
  {
    // Get work order IDs with completed plannings
    $workOrderIdsQuery = ApWorkOrderPlanning::query()
      ->where('status', 'completed')
      ->select('work_order_id');

    // Filter by user sedes
    if (!empty($userSedeIds)) {
      $workOrderIdsQuery->whereHas('workOrder', function ($q) use ($userSedeIds) {
        $q->whereIn('sede_id', $userSedeIds);
      });
    }

    // Apply filters
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      if ($column === 'sede_id' && $operator === '=') {
        $workOrderIdsQuery->whereHas('workOrder', function ($q) use ($value) {
          $q->where('sede_id', $value);
        });
      } elseif ($column === 'actual_end_datetime' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          $workOrderIdsQuery->whereRaw('DATE(actual_end_datetime) BETWEEN ? AND ?', [$value[0], $value[1]]);
        }
      }
    }

    $workOrderIds = $workOrderIdsQuery->pluck('work_order_id')->unique()->toArray();

    if (empty($workOrderIds)) {
      return collect();
    }

    // Get WorkOrderLabour (billed hours)
    $labours = WorkOrderLabour::query()
      ->with([
        'workOrder.sede',
        'workOrder.items.typePlanning',
        'workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        }
      ])
      ->whereIn('work_order_id', $workOrderIds)
      ->where('labour_type', '!=', WorkOrderLabour::LABOUR_TYPE_MATERIAL)
      ->where('labour_type', '!=', WorkOrderLabour::LABOUR_TYPE_DEDUCTIBLE)
      ->get();

    // Structure to accumulate hours by technician: [sede_id][worker_id] = hours
    $workerHours = [];

    // Process each labour
    foreach ($labours as $labour) {
      $workOrder = $labour->workOrder;
      $workOrderItem = $workOrder->items->first();

      if (!$workOrderItem || !$workOrderItem->typePlanning) {
        continue;
      }

      $billedHours = $labour->time_spent_decimal;
      $sedeId = $workOrder->sede_id ?? 'SIN_SEDE';

      // Get all technicians who worked on this work order
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      $totalWorkers = $plannings->count();

      if ($totalWorkers <= 0) {
        continue;
      }

      // Distribute billed hours equally among technicians
      foreach ($plannings as $planning) {
        $worker = $planning->worker;

        if (!$worker) {
          continue;
        }

        $equalBilledHours = $billedHours / $totalWorkers;

        // Initialize structure
        if (!isset($workerHours[$sedeId])) {
          $workerHours[$sedeId] = [];
        }

        if (!isset($workerHours[$sedeId][$worker->id])) {
          $workerHours[$sedeId][$worker->id] = [
            'worker' => $worker,
            'sede' => $workOrder->sede,
            'total_hours' => 0,
          ];
        }

        $workerHours[$sedeId][$worker->id]['total_hours'] += $equalBilledHours;
      }
    }

    // Convert structure to Collection
    $reportData = collect();

    foreach ($workerHours as $sedeId => $workers) {
      foreach ($workers as $workerId => $data) {
        $worker = $data['worker'];
        $sede = $data['sede'];

        $reportData->push([
          'sede_id' => $sedeId,
          'sede_name' => $sede ? $sede->description : 'SIN SEDE',
          'sede_abbreviation' => $sede ? $sede->abreviatura : 'SIN SEDE',
          'worker_id' => $workerId,
          'worker_dni' => $worker->vat ?? '',
          'worker_name' => $worker->nombre_completo ?? '',
          'billed_hours' => round($data['total_hours'], 2),
        ]);
      }
    }

    // Sort by sede and worker name
    return $reportData->sortBy([
      ['sede_abbreviation', 'asc'],
      ['worker_name', 'asc']
    ])->values();
  }

  /**
   * Calculate technician detail with productivity
   */
  private function calculateTechnicianDetail(
    \Illuminate\Support\Collection $billedData,
    array $period,
    float $workingHoursPerDay,
    float $earningsPerHour
  ): array {
    $technicianDetail = [];

    foreach ($billedData as $technician) {
      // Calculate standard hours for the period
      $standardHours = $period['working_days'] * $workingHoursPerDay;

      // Get billed hours
      $billedHours = $technician['billed_hours'];

      // Calculate productivity (can be negative)
      $productivityHours = $billedHours - $standardHours;

      // Calculate earnings
      $earnings = $productivityHours * $earningsPerHour;

      // Calculate productivity percentage
      $productivityPercentage = $standardHours > 0
        ? round(($billedHours / $standardHours) * 100, 2)
        : 0;

      // Determine status
      $status = $this->getProductivityStatus($productivityPercentage);

      $technicianDetail[] = [
        'sede_id' => $technician['sede_id'],
        'sede_name' => $technician['sede_name'],
        'sede_abbreviation' => $technician['sede_abbreviation'],
        'worker_id' => $technician['worker_id'],
        'worker_dni' => $technician['worker_dni'],
        'worker_name' => $technician['worker_name'],
        'standard_hours' => round($standardHours, 2),
        'billed_hours' => $billedHours,
        'productivity_hours' => round($productivityHours, 2),
        'productivity_percentage' => $productivityPercentage,
        'earnings' => round($earnings, 2),
        'status' => $status
      ];
    }

    // Sort by productivity hours DESC
    usort($technicianDetail, function ($a, $b) {
      return $b['productivity_hours'] <=> $a['productivity_hours'];
    });

    // Add ranking
    $rank = 1;
    foreach ($technicianDetail as &$tech) {
      $tech['rank'] = $rank++;
    }

    return $technicianDetail;
  }

  /**
   * Calculate headquarters summary
   */
  private function calculateHeadquartersSummary(array $technicianDetail): array
  {
    $headquartersSummary = [];

    // Group by sede
    $groupedBySede = [];
    foreach ($technicianDetail as $tech) {
      $sedeId = $tech['sede_id'];
      if (!isset($groupedBySede[$sedeId])) {
        $groupedBySede[$sedeId] = [
          'sede_id' => $sedeId,
          'sede_name' => $tech['sede_name'],
          'sede_abbreviation' => $tech['sede_abbreviation'],
          'technician_count' => 0,
          'total_standard_hours' => 0,
          'total_billed_hours' => 0,
          'total_productivity_hours' => 0,
          'total_earnings' => 0,
        ];
      }

      $groupedBySede[$sedeId]['technician_count']++;
      $groupedBySede[$sedeId]['total_standard_hours'] += $tech['standard_hours'];
      $groupedBySede[$sedeId]['total_billed_hours'] += $tech['billed_hours'];
      $groupedBySede[$sedeId]['total_productivity_hours'] += $tech['productivity_hours'];
      $groupedBySede[$sedeId]['total_earnings'] += $tech['earnings'];
    }

    // Calculate averages and status for each sede
    foreach ($groupedBySede as $sede) {
      $avgProductivityPercentage = $sede['total_standard_hours'] > 0
        ? round(($sede['total_billed_hours'] / $sede['total_standard_hours']) * 100, 2)
        : 0;

      $headquartersSummary[] = [
        'sede_id' => $sede['sede_id'],
        'sede_name' => $sede['sede_name'],
        'sede_abbreviation' => $sede['sede_abbreviation'],
        'technician_count' => $sede['technician_count'],
        'total_standard_hours' => round($sede['total_standard_hours'], 2),
        'total_billed_hours' => round($sede['total_billed_hours'], 2),
        'total_productivity_hours' => round($sede['total_productivity_hours'], 2),
        'total_earnings' => round($sede['total_earnings'], 2),
        'average_productivity_percentage' => $avgProductivityPercentage,
        'status' => $this->getProductivityStatus($avgProductivityPercentage)
      ];
    }

    // Sort by total productivity hours DESC
    usort($headquartersSummary, function ($a, $b) {
      return $b['total_productivity_hours'] <=> $a['total_productivity_hours'];
    });

    // Add ranking
    $rank = 1;
    foreach ($headquartersSummary as &$hq) {
      $hq['rank'] = $rank++;
    }

    return $headquartersSummary;
  }

  /**
   * Calculate executive summary
   */
  private function calculateExecutiveSummary(array $technicianDetail, array $headquartersSummary): array
  {
    if (empty($technicianDetail)) {
      return $this->getEmptyExecutiveSummary();
    }

    $totalStandardHours = array_sum(array_column($technicianDetail, 'standard_hours'));
    $totalBilledHours = array_sum(array_column($technicianDetail, 'billed_hours'));
    $totalProductivityHours = array_sum(array_column($technicianDetail, 'productivity_hours'));
    $totalEarnings = array_sum(array_column($technicianDetail, 'earnings'));

    $avgProductivityPercentage = $totalStandardHours > 0
      ? round(($totalBilledHours / $totalStandardHours) * 100, 2)
      : 0;

    $totalTechnicians = count($technicianDetail);
    $totalHeadquarters = count($headquartersSummary);

    // Count technicians by status
    $statusCount = [
      'exceeded' => 0,
      'on_track' => 0,
      'warning' => 0,
      'critical' => 0
    ];

    foreach ($technicianDetail as $tech) {
      $status = $tech['status'];
      if (isset($statusCount[$status])) {
        $statusCount[$status]++;
      }
    }

    return [
      'total_technicians' => $totalTechnicians,
      'total_headquarters' => $totalHeadquarters,
      'total_standard_hours' => round($totalStandardHours, 2),
      'total_billed_hours' => round($totalBilledHours, 2),
      'total_productivity_hours' => round($totalProductivityHours, 2),
      'total_earnings' => round($totalEarnings, 2),
      'average_productivity_percentage' => $avgProductivityPercentage,
      'status' => $this->getProductivityStatus($avgProductivityPercentage),
      'status_breakdown' => $statusCount
    ];
  }

  /**
   * Generate chart data for frontend
   */
  private function generateChartData(array $technicianDetail): array
  {
    // Take top 10 technicians by productivity
    $top10 = array_slice($technicianDetail, 0, 10);

    $labels = array_column($top10, 'worker_name');

    return [
      'labels' => $labels,
      'datasets' => [
        'standard_hours' => array_column($top10, 'standard_hours'),
        'billed_hours' => array_column($top10, 'billed_hours'),
        'productivity_hours' => array_column($top10, 'productivity_hours'),
        'earnings' => array_column($top10, 'earnings'),
        'productivity_percentage' => array_column($top10, 'productivity_percentage')
      ]
    ];
  }

  /**
   * Get productivity status based on percentage
   */
  private function getProductivityStatus(float $percentage): string
  {
    if ($percentage < 70) {
      return 'critical';
    } elseif ($percentage < 85) {
      return 'warning';
    } elseif ($percentage <= 100) {
      return 'on_track';
    } else {
      return 'exceeded';
    }
  }

  /**
   * Get empty executive summary
   */
  private function getEmptyExecutiveSummary(): array
  {
    return [
      'total_technicians' => 0,
      'total_headquarters' => 0,
      'total_standard_hours' => 0,
      'total_billed_hours' => 0,
      'total_productivity_hours' => 0,
      'total_earnings' => 0,
      'average_productivity_percentage' => 0,
      'status' => 'not_applicable',
      'status_breakdown' => [
        'exceeded' => 0,
        'on_track' => 0,
        'warning' => 0,
        'critical' => 0
      ]
    ];
  }

  /**
   * Get user sede IDs
   */
  private function getUserSedeIds(): array
  {
    $user = Auth::user();

    if (!$user) {
      return [];
    }

    return UserSede::where('user_id', $user->id)
      ->where('status', true)
      ->pluck('sede_id')
      ->toArray();
  }
}