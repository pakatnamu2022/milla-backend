<?php

namespace App\Http\Services\ap\postventa\Dashboard;

use App\Http\Services\ap\postventa\Shared\BilledHoursCalculationService;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;

class TechnicianProductivityDetailService
{
  protected BilledHoursCalculationService $billedHoursService;
  protected ProductivityDashboardService $productivityDashboardService;

  public function __construct(
    BilledHoursCalculationService $billedHoursService,
    ProductivityDashboardService $productivityDashboardService
  )
  {
    $this->billedHoursService = $billedHoursService;
    $this->productivityDashboardService = $productivityDashboardService;
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
    $billedData = $this->billedHoursService->calculateBilledHoursForWorker($labours, $workerId, $workOrders, $startDate, $endDate);

    // Get attendance data
    $attendanceData = $this->getAttendanceData($workerId, $startDate, $endDate);

    // Consolidate work orders (group labours by OT)
    $consolidatedWorkOrders = $this->consolidateWorkOrders($billedData['work_orders_detail']);

    // Calculate summary (incluye days_worked real del técnico)
    // Usamos el count de OTs consolidadas (únicas)
    $summary = $this->calculateSummaryFromBilledData(
      $billedData['total_billed_hours'],
      $attendanceData,
      count($consolidatedWorkOrders)
    );

    // Validate that sums match
    $validation = $this->validateSums($billedData['work_orders_detail'], $summary);

    // Get technician detail for all technicians in the sede (if sedeId is provided)
    $technicianDetail = [];
    if ($sedeId) {
      $technicianDetailData = $this->productivityDashboardService->getTechnicianDetailBySede(
        $startDate,
        $endDate,
        $sedeId,
        true // use cache
      );
      $technicianDetail = $technicianDetailData['technician_detail'] ?? [];
    }

    return [
      'technician_info' => $this->getTechnicianInfo($technician),
      'period' => $period,
      'summary' => $summary,
      'work_orders' => $consolidatedWorkOrders,
      'work_orders_without_labour' => $billedData['work_orders_without_labour'],
      'validation' => $validation,
      'technician_detail' => $technicianDetail
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
    $daysWorked = $attendanceData['days_worked'];

    // Calculate productivity hours
    $productivityHours = $totalBilledHours - $standardHours;

    // Calculate commission
    // IMPORTANTE: Si standard_hours = 0, NO hay comisión (alerta para regularizar asistencias)
    // Esto evita dar comisiones falsas a técnicos sin asistencias registradas
    $earningsPerHour = 8.0;
    if ($standardHours > 0) {
      $commission = max(0, $productivityHours) * $earningsPerHour;
    } else {
      $commission = 0; // No hay asistencias registradas
    }

    // Calculate productivity percentage
    $productivityPercentage = $standardHours > 0
      ? round(($totalBilledHours / $standardHours) * 100, 2)
      : 0;

    return [
      'days_worked' => $daysWorked,
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
   * MÉTODO REFACTORIZADO: Ahora usa el servicio centralizado BilledHoursCalculationService
   */
  private function getAttendanceData(int $workerId, string $startDate, string $endDate): array
  {
    // Usar el método centralizado del servicio compartido
    return $this->billedHoursService->getAttendanceData($workerId, $startDate, $endDate);
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
   * Consolidate work orders by grouping labours/trabajos under each unique OT
   *
   * @param array $workOrdersDetail Raw detail with one row per labour
   * @return array Consolidated array with one row per OT and trabajos nested inside
   */
  private function consolidateWorkOrders(array $workOrdersDetail): array
  {
    $consolidated = [];

    foreach ($workOrdersDetail as $detail) {
      $workOrderId = $detail['work_order_id'];

      // If this OT hasn't been added yet, create the base structure
      if (!isset($consolidated[$workOrderId])) {
        $consolidated[$workOrderId] = [
          'work_order_id' => $detail['work_order_id'],
          'work_order_number' => $detail['work_order_number'],
          'vehicle_plate' => $detail['vehicle_plate'],
          'sede' => $detail['sede'],
          'asesor' => $detail['asesor'],
          'fecha_facturacion' => $detail['fecha_facturacion'],
          'tipo_planificacion' => $detail['tipo_planificacion'],
          'categoria_tipo' => $detail['categoria_tipo'],
          'horas_facturadas_total_ot' => 0,
          'cantidad_tecnicos' => $detail['cantidad_tecnicos'],
          'tiene_mano_obra' => $detail['tiene_mano_obra'],
          'trabajos' => []
        ];
      }

      // Add this labour/trabajo to the OT's trabajos array
      $consolidated[$workOrderId]['trabajos'][] = [
        'descripcion_labour' => $detail['descripcion_labour'],
        'horas_facturadas_tecnico' => $detail['horas_facturadas_tecnico'],
        'labour_hourly_rate' => $detail['labour_hourly_rate'] ?? null,
        'labour_current_hourly_cost' => $detail['labour_current_hourly_cost'] ?? null
      ];

      // Accumulate total hours for this OT
      $consolidated[$workOrderId]['horas_facturadas_total_ot'] += $detail['horas_facturadas_tecnico'];
    }

    // Round total hours and return indexed array (remove keys)
    return array_values(array_map(function ($ot) {
      $ot['horas_facturadas_total_ot'] = round($ot['horas_facturadas_total_ot'], 2);
      return $ot;
    }, $consolidated));
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
