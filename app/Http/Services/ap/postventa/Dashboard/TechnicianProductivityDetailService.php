<?php

namespace App\Http\Services\ap\postventa\Dashboard;

use App\Http\Services\ap\postventa\Shared\BilledHoursCalculationService;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;

class TechnicianProductivityDetailService
{
  protected BilledHoursCalculationService $billedHoursService;

  public function __construct(BilledHoursCalculationService $billedHoursService)
  {
    $this->billedHoursService = $billedHoursService;
  }
  /**
   * Get detailed productivity information for a specific technician
   *
   * @param int $workerId
   * @param string $startDate Format: Y-m-d
   * @param string $endDate Format: Y-m-d
   * @param int|null $sedeId Optional filter by sede
   * @return array
   */
  public function getTechnicianProductivityDetail(int $workerId, string $startDate, string $endDate, ?int $sedeId = null): array
  {
    // Get technician info
    $technician = Worker::find($workerId);

    if (!$technician) {
      throw new \Exception("Técnico no encontrado con ID: {$workerId}");
    }

    // Get period info
    $period = $this->getPeriodInfo($startDate, $endDate);

    // Get user sede IDs
    $userSedeIds = $this->billedHoursService->getUserSedeIds();

    // Get billed hours data usando el servicio centralizado
    $labours = $this->billedHoursService->getBilledHoursData($startDate, $endDate, $sedeId, $userSedeIds);
    $workOrders = $this->billedHoursService->getWorkOrders($startDate, $endDate, $sedeId, $userSedeIds);

    // Calculate billed hours for this specific worker
    $billedData = $this->billedHoursService->calculateBilledHoursForWorker($labours, $workerId, $workOrders);

    // Get attendance data
    $attendanceData = $this->getAttendanceData($workerId, $startDate, $endDate);

    // Calculate summary
    $summary = $this->calculateSummaryFromBilledData(
      $billedData['total_billed_hours'],
      $attendanceData,
      count($billedData['work_orders_detail'])
    );

    // Validate that sums match
    $validation = $this->validateSums($billedData['work_orders_detail'], $summary);

    return [
      'technician_info' => $this->getTechnicianInfo($technician),
      'period' => $period,
      'summary' => $summary,
      'work_orders' => $billedData['work_orders_detail'],
      'work_orders_without_labour' => $billedData['work_orders_without_labour'],
      'validation' => $validation
    ];
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
   * Get technician basic info
   */
  private function getTechnicianInfo(Worker $technician): array
  {
    return [
      'worker_id' => $technician->id,
      'worker_name' => $technician->nombre_completo ?? '',
      'worker_dni' => $technician->vat ?? '',
    ];
  }

  /**
   * Calculate summary from billed data and attendance data
   */
  private function calculateSummaryFromBilledData(float $totalBilledHours, array $attendanceData, int $totalWorkOrders): array
  {
    $standardHours = $attendanceData['standard_hours'];
    $realHours = $attendanceData['real_hours'];

    // Calculate productivity hours
    $productivityHours = $totalBilledHours - $standardHours;

    // Calculate commission (only positive productivity hours)
    $earningsPerHour = 8.0;
    $commission = max(0, $productivityHours) * $earningsPerHour;

    // Calculate productivity percentage
    $productivityPercentage = $standardHours > 0
      ? round(($totalBilledHours / $standardHours) * 100, 2)
      : 0;

    return [
      'real_hours' => round($realHours, 2),
      'standard_hours' => round($standardHours, 2),
      'billed_hours' => round($totalBilledHours, 2),
      'productivity_hours' => round($productivityHours, 2),
      'productivity_percentage' => $productivityPercentage,
      'commission' => round($commission, 2),
      'earnings_per_hour' => $earningsPerHour,
      'total_work_orders' => $totalWorkOrders
    ];
  }

  /**
   * Get attendance data for a technician (standard hours and real hours)
   */
  private function getAttendanceData(int $workerId, string $startDate, string $endDate): array
  {
    try {
      $attendanceService = new \App\Http\Services\gp\gestionhumana\asistencias\AttendanceSyncService();

      $attendanceRequest = new \Illuminate\Http\Request([
        'date_from' => $startDate,
        'date_to' => $endDate,
      ]);

      $attendanceResponse = $attendanceService->personDashboard(
        $workerId,
        $attendanceRequest
      );

      $attendanceData = $attendanceResponse->getData(true);

      // Count days with check_in
      $daysWorked = collect($attendanceData['daily'])
        ->filter(function ($day) {
          return $day['type'] === 'work' && !empty($day['check_in']);
        })
        ->count();

      // Standard hours: 8h × days with check_in
      $standardHours = $daysWorked * 8;

      // Real hours: Parse from "XXXh YYmin" format
      $realHours = $this->parseHoursFromString($attendanceData['hours_worked']);

      return [
        'standard_hours' => $standardHours,
        'real_hours' => $realHours,
      ];
    } catch (\Exception $e) {
      // If it fails, return 0 for both
      return [
        'standard_hours' => 0,
        'real_hours' => 0,
      ];
    }
  }

  /**
   * Parse hours from string format "XXXh YYmin" to decimal
   */
  private function parseHoursFromString(string $hoursString): float
  {
    // Format: "246h 11min"
    preg_match('/(\d+)h(?: (\d+)min)?/', $hoursString, $matches);

    $hours = isset($matches[1]) ? (int)$matches[1] : 0;
    $minutes = isset($matches[2]) ? (int)$matches[2] : 0;

    return $hours + ($minutes / 60);
  }

  /**
   * Validate that detail sums match summary totals
   * Allows small differences due to rounding (tolerance: 1% or max 5 hours)
   */
  private function validateSums(array $workOrdersDetail, array $summary): array
  {
    $sumaDetalleHorasFacturadas = round(array_sum(array_column($workOrdersDetail, 'horas_facturadas_tecnico')), 2);
    $totalResumenHorasFacturadas = round($summary['billed_hours'], 2);

    $diferencia = abs($sumaDetalleHorasFacturadas - $totalResumenHorasFacturadas);
    $porcentajeDiferencia = $totalResumenHorasFacturadas > 0
      ? ($diferencia / $totalResumenHorasFacturadas) * 100
      : 0;

    // Consider it valid if difference is less than 1% or less than 5 hours
    $cuadraHorasFacturadas = $diferencia < 5 || $porcentajeDiferencia < 1;

    return [
      'suma_detalle_horas_facturadas' => $sumaDetalleHorasFacturadas,
      'total_resumen_horas_facturadas' => $totalResumenHorasFacturadas,
      'diferencia' => round($diferencia, 2),
      'porcentaje_diferencia' => round($porcentajeDiferencia, 2),
      'cuadra' => $cuadraHorasFacturadas,
      'mensaje' => $cuadraHorasFacturadas
        ? 'Validación correcta'
        : 'Advertencia: Diferencia mayor al 1% o 5 horas entre detalle y resumen'
    ];
  }

  /**
   * Get user sede IDs (same as ProductivityDashboardService)
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
