<?php

namespace App\Http\Services\ap\postventa\Dashboard;

use App\Http\Services\ap\postventa\Shared\BilledHoursCalculationService;
use App\Models\GeneralMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProductivityDashboardService
{
  protected BilledHoursCalculationService $billedHoursService;

  public function __construct(BilledHoursCalculationService $billedHoursService)
  {
    $this->billedHoursService = $billedHoursService;
  }

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
      $userSedeIds = $this->billedHoursService->getUserSedeIds();

      // Get billed hours data usando el servicio centralizado
      $labours = $this->billedHoursService->getBilledHoursData($startDate, $endDate, $sedeId, $userSedeIds);

      // Calculate billed hours by technician
      $billedData = $this->billedHoursService->calculateBilledHoursByWorker($labours);

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
   * Calculate technician detail with productivity
   */
  private function calculateTechnicianDetail(
    \Illuminate\Support\Collection $billedData,
    array                          $period,
    float                          $workingHoursPerDay,
    float                          $earningsPerHour
  ): array
  {
    $technicianDetail = [];
    $attendanceService = new \App\Http\Services\gp\gestionhumana\asistencias\AttendanceSyncService();

    foreach ($billedData as $technician) {
      // Usar AttendanceSyncService como ÚNICA fuente de verdad
      try {
        $attendanceRequest = new \Illuminate\Http\Request([
          'date_from' => $period['start_date'],
          'date_to' => $period['end_date'],
        ]);

        $attendanceResponse = $attendanceService->personDashboard(
          $technician['worker_id'],
          $attendanceRequest
        );

        $attendanceData = $attendanceResponse->getData(true);
      } catch (\Exception $e) {
        // Si falla la consulta de asistencias
        $technicianDetail[] = [
          'sede_id' => $technician['sede_id'],
          'sede_name' => $technician['sede_name'],
          'sede_abbreviation' => $technician['sede_abbreviation'],
          'worker_id' => $technician['worker_id'],
          'worker_dni' => $technician['worker_dni'],
          'worker_name' => $technician['worker_name'],
          'has_error' => true,
          'error_message' => 'Error al obtener datos de asistencia: ' . $e->getMessage(),
          'days_worked' => 0,
          'standard_hours' => 0,
          'real_hours' => 0,
          'billed_hours' => $technician['billed_hours'],
          'productivity_hours' => 0,
          'productivity_percentage' => 0,
          'earnings' => 0,
          'status' => 'error',
          'rank' => 0,
        ];
        continue;
      }

      // Extraer días trabajados (días con check_in) de los datos daily
      $daysWorked = collect($attendanceData['daily'])
        ->filter(function ($day) {
          return $day['type'] === 'work' && !empty($day['check_in']);
        })
        ->count();

      // Horas estándar: 8h × días con check_in
      $standardHours = $daysWorked * 8;

      // Horas reales: Parsear del formato "XXXh YYmin" del endpoint
      $realHours = $this->parseHoursFromString($attendanceData['hours_worked']);

      // Get billed hours
      $billedHours = $technician['billed_hours'];

      // Calculate productivity (can be negative)
      $productivityHours = $billedHours - $standardHours;

      // Calculate earnings (if negative, set to 0)
      $earnings = max(0, $productivityHours * $earningsPerHour);

      // Calculate productivity percentage
      $productivityPercentage = $standardHours > 0
        ? round(($billedHours / $standardHours) * 100, 2)
        : 0;

      // Determine status
      $status = $this->getProductivityStatus($productivityPercentage);

      // Contar días con marcaciones incompletas
      $daysWithMissingMarks = collect($attendanceData['daily'])
        ->filter(function ($day) {
          return $day['type'] === 'work'
            && !empty($day['check_in'])
            && (empty($day['check_out']) || empty($day['lunch_out']) || empty($day['lunch_in']));
        })
        ->values()
        ->map(function ($day) {
          $missing = [];
          if (empty($day['check_out'])) $missing[] = 'check_out';
          if (empty($day['lunch_out'])) $missing[] = 'lunch_out';
          if (empty($day['lunch_in'])) $missing[] = 'lunch_in';

          return [
            'date' => $day['date'],
            'missing_marks' => $missing,
          ];
        });

      $technicianDetail[] = [
        'sede_id' => $technician['sede_id'],
        'sede_name' => $technician['sede_name'],
        'sede_abbreviation' => $technician['sede_abbreviation'],
        'worker_id' => $technician['worker_id'],
        'worker_dni' => $technician['worker_dni'],
        'worker_name' => $technician['worker_name'],
        'has_error' => false,
        'days_worked' => $daysWorked,
        'standard_hours' => round($standardHours, 2),
        'real_hours' => round($realHours, 2),
        'billed_hours' => $billedHours,
        'productivity_hours' => round($productivityHours, 2),
        'productivity_percentage' => $productivityPercentage,
        'earnings' => round($earnings, 2),
        'status' => $status,
        // Información de asistencia
        'attendance_summary' => [
          'days_with_checkin' => $daysWorked,
          'days_with_checkout' => collect($attendanceData['daily'])->filter(fn($d) => $d['type'] === 'work' && !empty($d['check_out']))->count(),
          'days_with_missing_marks' => $daysWithMissingMarks->count(),
          'missing_marks_details' => $daysWithMissingMarks->toArray(),
        ],
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
   * Parse hours from string format "XXXh YYmin" to decimal
   */
  private function parseHoursFromString(string $hoursString): float
  {
    // Formato: "246h 11min"
    preg_match('/(\d+)h(?: (\d+)min)?/', $hoursString, $matches);

    $hours = isset($matches[1]) ? (int)$matches[1] : 0;
    $minutes = isset($matches[2]) ? (int)$matches[2] : 0;

    return $hours + ($minutes / 60);
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
}
