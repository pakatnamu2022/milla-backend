<?php

namespace App\Http\Services\ap\postventa\Shared;

use App\Http\Services\ap\postventa\Reports\ClosedWorkOrdersService;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApCampaignSchedule;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio centralizado para el cálculo de horas facturadas
 * Usado por ProductivityDashboardService, TechnicianProductivityDetailService y ClosedWorkOrderBilledHoursReportService
 */
class BilledHoursCalculationService
{
  protected ClosedWorkOrdersService $closedWorkOrdersService;

  public function __construct(ClosedWorkOrdersService $closedWorkOrdersService)
  {
    $this->closedWorkOrdersService = $closedWorkOrdersService;
  }

  /**
   * Obtiene las horas facturadas por técnico para un rango de fechas
   *
   * @param string $startDate Fecha inicio (Y-m-d)
   * @param string $endDate Fecha fin (Y-m-d)
   * @param int|null $sedeId Filtro opcional por sede
   * @param array $userSedeIds Filtro opcional por sedes del usuario autenticado
   * @return Collection Collection de labours con workOrder y plannings cargados
   */
  public function getBilledHoursData(string $startDate, string $endDate, ?int $sedeId = null, array $userSedeIds = []): Collection
  {
    // 1. Obtener todas las work orders del período
    $workOrders = $this->getWorkOrders($startDate, $endDate, $sedeId, $userSedeIds);

    if ($workOrders->isEmpty()) {
      return collect();
    }

    $workOrderIds = $workOrders->pluck('id')->unique()->toArray();

    // 2. Obtener los labours (horas facturadas) de esas work orders
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

    return $labours;
  }

  /**
   * Calcula las horas facturadas agrupadas por técnico
   * Aplica la lógica de negocio: técnicos únicos y distribución equitativa
   *
   * @param Collection $labours Labours obtenidos de getBilledHoursData()
   * @return Collection [sede_id][worker_id] => ['worker' => Worker, 'sede' => Sede, 'total_hours' => float]
   */
  public function calculateBilledHoursByWorker(Collection $labours): Collection
  {
    $workerHours = [];

    foreach ($labours as $labour) {
      $workOrder = $labour->workOrder;
      $workOrderItem = $workOrder->items->first();

      if (!$workOrderItem || !$workOrderItem->typePlanning) {
        continue;
      }

      // Obtener el tipo de cambio si la OT está en dólares (USD)
      // Si es USD (currency_id = 1), multiplicar por exchange_rate
      // Si es PEN (currency_id = 3) o cualquier otra moneda, usar 1 como multiplicador
      $exchangeRate = ($workOrder->currency_id == TypeCurrency::USD_ID && $workOrder->exchange_rate > 0)
        ? $workOrder->exchange_rate
        : 1;

      // Calcular horas facturadas equivalentes: (hourly_rate * time_spent * exchange_rate) / current_hourly_cost
      $billedHours = $labour->current_hourly_cost > 0
        ? (($labour->hourly_rate * $labour->time_spent_decimal) * $exchangeRate) / $labour->current_hourly_cost
        : 0;

      $sedeId = $workOrder->sede_id ?? 'SIN_SEDE';

      // Obtener todos los técnicos que trabajaron en esta OT
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      // REGLA DE NEGOCIO: Consolidar técnicos ÚNICOS (ignorar planificaciones duplicadas del mismo técnico)
      $uniqueWorkers = $plannings->unique('worker_id');
      $totalWorkers = $uniqueWorkers->count();

      if ($totalWorkers <= 0) {
        continue;
      }

      // Distribuir las horas facturadas en partes iguales entre los técnicos ÚNICOS
      foreach ($uniqueWorkers as $planning) {
        $worker = $planning->worker;

        if (!$worker) {
          continue;
        }

        // Calcular la distribución igual: horas facturadas / número de técnicos ÚNICOS
        $equalBilledHours = $billedHours / $totalWorkers;

        // Inicializar estructura si no existe
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

        // Acumular las horas en partes iguales
        $workerHours[$sedeId][$worker->id]['total_hours'] += $equalBilledHours;
      }
    }

    // Convertir la estructura a Collection
    $reportData = collect();

    foreach ($workerHours as $sedeId => $workers) {
      foreach ($workers as $workerId => $data) {
        $worker = $data['worker'];
        $sede = $data['sede'];

        $reportData->push([
          'sede_id' => $sedeId,
          'sede_name' => $sede ? $sede->abreviatura : 'SIN SEDE',
          'sede_abbreviation' => $sede ? $sede->abreviatura : 'SIN SEDE',
          'worker_id' => $workerId,
          'worker_dni' => $worker->vat ?? '',
          'worker_name' => $worker->nombre_completo ?? '',
          'billed_hours' => round($data['total_hours'], 2),
        ]);
      }
    }

    return $reportData;
  }

  /**
   * Calcula las horas facturadas para un técnico específico
   *
   * @param Collection $labours Labours obtenidos de getBilledHoursData()
   * @param int $workerId ID del técnico
   * @param Collection $workOrders Work orders del período
   * @param string|null $startDate Fecha inicio para filtrar documentos (Y-m-d)
   * @param string|null $endDate Fecha fin para filtrar documentos (Y-m-d)
   * @return array ['total_billed_hours' => float, 'work_orders_detail' => array, 'work_orders_without_labour' => array]
   */
  public function calculateBilledHoursForWorker(Collection $labours, int $workerId, Collection $workOrders, ?string $startDate = null, ?string $endDate = null): array
  {
    $totalBilledHoursForTechnician = 0;
    $workOrdersDetail = [];
    $workOrdersWithLabourIds = [];

    foreach ($labours as $labour) {
      $workOrder = $labour->workOrder;
      $workOrderItem = $workOrder->items->first();

      if (!$workOrderItem || !$workOrderItem->typePlanning) {
        continue;
      }

      // Obtener el tipo de cambio si la OT está en dólares (USD)
      // Si es USD (currency_id = 1), multiplicar por exchange_rate
      // Si es PEN (currency_id = 3) o cualquier otra moneda, usar 1 como multiplicador
      $exchangeRate = ($workOrder->currency_id == TypeCurrency::USD_ID && $workOrder->exchange_rate > 0)
        ? $workOrder->exchange_rate
        : 1;

      // Calcular horas facturadas equivalentes: (hourly_rate * time_spent * exchange_rate) / current_hourly_cost
      $billedHours = $labour->current_hourly_cost > 0
        ? (($labour->hourly_rate * $labour->time_spent_decimal) * $exchangeRate) / $labour->current_hourly_cost
        : 0;

      // Obtener todos los técnicos que trabajaron en esta OT
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      // Consolidar técnicos ÚNICOS
      $uniqueWorkers = $plannings->unique('worker_id');
      $totalWorkers = $uniqueWorkers->count();

      if ($totalWorkers <= 0) {
        continue;
      }

      // Verificar si el técnico especificado trabajó en esta OT
      $technicianPlanning = $uniqueWorkers->firstWhere('worker_id', $workerId);

      if (!$technicianPlanning) {
        continue; // El técnico no trabajó en esta OT
      }

      // Marcar esta work order como teniendo labour
      $workOrdersWithLabourIds[] = $workOrder->id;

      // Distribuir horas equitativamente entre técnicos ÚNICOS
      $equalBilledHours = $billedHours / $totalWorkers;

      // Acumular para este técnico
      $totalBilledHoursForTechnician += $equalBilledHours;

      // Obtener fecha de facturación (dentro del rango si está disponible)
      $invoiceDate = $this->getInvoiceDate($workOrder, $startDate, $endDate);

      // Agregar al detalle
      $workOrdersDetail[] = [
        'work_order_id' => $workOrder->id,
        'work_order_number' => $workOrder->correlative ?? '',
        'vehicle_plate' => $workOrder->vehicle ? $workOrder->vehicle->plate : '',
        'sede' => $workOrder->sede ? $workOrder->sede->abreviatura : 'SIN SEDE',
        'asesor' => $workOrder->advisor ? $workOrder->advisor->nombre_completo : 'N/A',
        'fecha_facturacion' => $invoiceDate,
        'tipo_planificacion' => $workOrderItem->typePlanning->description ?? '',
        'categoria_tipo' => $workOrderItem->typePlanning->category_type ?? '',
        'descripcion_labour' => $labour->description ?? '',
        'horas_facturadas_total_ot' => round($billedHours, 2),
        'cantidad_tecnicos' => $totalWorkers,
        'horas_facturadas_tecnico' => round($equalBilledHours, 2),
        'tiene_mano_obra' => true,
        'labour_hourly_rate' => round($labour->hourly_rate, 2),
        'labour_current_hourly_cost' => round($labour->current_hourly_cost, 2),
      ];
    }

    // Detectar OTs donde el técnico trabajó pero no hay labour cargado
    $workOrdersWithoutLabour = [];
    $workOrdersWithLabourIds = array_unique($workOrdersWithLabourIds);

    foreach ($workOrders as $workOrder) {
      $technicianPlanning = $workOrder->plannings->firstWhere('worker_id', $workerId);

      if ($technicianPlanning && !in_array($workOrder->id, $workOrdersWithLabourIds)) {
        $workOrderItem = $workOrder->items->first();
        $invoiceDate = $this->getInvoiceDate($workOrder, $startDate, $endDate);

        $workOrdersWithoutLabour[] = [
          'work_order_id' => $workOrder->id,
          'work_order_number' => $workOrder->correlative ?? '',
          'vehicle_plate' => $workOrder->vehicle ? $workOrder->vehicle->plate : '',
          'sede' => $workOrder->sede ? $workOrder->sede->abreviatura : 'SIN SEDE',
          'asesor' => $workOrder->advisor ? $workOrder->advisor->nombre_completo : 'N/A',
          'fecha_facturacion' => $invoiceDate,
          'tipo_planificacion' => $workOrderItem && $workOrderItem->typePlanning ? $workOrderItem->typePlanning->description : 'N/A',
          'observacion' => 'OT sin mano de obra cargada'
        ];
      }
    }

    // Ordenar por fecha de facturación DESC
    usort($workOrdersDetail, function ($a, $b) {
      return strcmp($b['fecha_facturacion'], $a['fecha_facturacion']);
    });

    return [
      'total_billed_hours' => round($totalBilledHoursForTechnician, 2),
      'work_orders_detail' => $workOrdersDetail,
      'work_orders_without_labour' => $workOrdersWithoutLabour
    ];
  }

  /**
   * Obtiene todas las work orders del período aplicando los filtros
   * Usa el servicio centralizado ClosedWorkOrdersService
   *
   * @param string $startDate
   * @param string $endDate
   * @param int|null $sedeId
   * @param array $userSedeIds
   * @return Collection
   */
  public function getWorkOrders(string $startDate, string $endDate, ?int $sedeId, array $userSedeIds): Collection
  {
    // Usar el servicio centralizado para obtener OTs cerradas
    $workOrders = $this->closedWorkOrdersService->getClosedWorkOrders(
      [$startDate, $endDate],
      $sedeId,
      $userSedeIds
    );

    // Recargar plannings con filtros específicos para horas facturadas
    // (el servicio centralizado carga todas las relaciones, pero necesitamos filtrar plannings)
    $workOrders->load([
      'plannings' => function ($query) {
        $query->where('status', 'completed')
          ->whereNotNull('worker_id')
          ->with('worker');
      }
    ]);

    return $workOrders;
  }

  /**
   * Obtiene la fecha de facturación de una work order
   * Prioriza documentos dentro del rango de fechas si se proporcionan
   *
   * @param ApWorkOrder $workOrder
   * @param string|null $startDate Fecha inicio del rango (Y-m-d)
   * @param string|null $endDate Fecha fin del rango (Y-m-d)
   * @return string
   */
  private function getInvoiceDate(ApWorkOrder $workOrder, ?string $startDate = null, ?string $endDate = null): string
  {
    // Try to get from electronic document
    $query = ElectronicDocument::query()
      ->where('work_order_id', $workOrder->id)
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED]);

    // Si se proporciona rango de fechas, priorizar documento dentro del rango
    if ($startDate && $endDate) {
      $electronicDocumentInRange = $query->clone()
        ->whereBetween('fecha_de_emision', [$startDate, $endDate])
        ->orderBy('fecha_de_emision', 'desc')
        ->first();

      if ($electronicDocumentInRange) {
        return $electronicDocumentInRange->fecha_de_emision;
      }
    }

    // Si no hay documento en el rango o no se proporcionó rango, tomar el más reciente
    $electronicDocument = $query->orderBy('fecha_de_emision', 'desc')->first();

    if ($electronicDocument) {
      return $electronicDocument->fecha_de_emision;
    }

    // Try to get from internal note
    $internalNoteQuery = $workOrder->internalNotes()->whereNotNull('number');

    if ($startDate && $endDate) {
      $internalNoteInRange = $internalNoteQuery->clone()
        ->whereBetween('created_date', [$startDate, $endDate])
        ->orderBy('created_date', 'desc')
        ->first();

      if ($internalNoteInRange) {
        return $internalNoteInRange->created_date;
      }
    }

    $internalNote = $internalNoteQuery->orderBy('created_date', 'desc')->first();
    if ($internalNote) {
      return $internalNote->created_date;
    }

    return '';
  }

  /**
   * Obtiene las sedes del usuario autenticado
   *
   * @return array
   */
  public function getUserSedeIds(): array
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

  /**
   * Calcula las horas estándar para un técnico basado en sus asistencias
   * MÉTODO CENTRALIZADO usado por todos los servicios de productividad
   *
   * @param int $workerId ID del técnico
   * @param string $startDate Fecha inicio (Y-m-d)
   * @param string $endDate Fecha fin (Y-m-d)
   * @return float Horas estándar (días con check_in × 8h)
   */
  public function calculateStandardHours(int $workerId, string $startDate, string $endDate): float
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

      // Contar días con check_in
      $daysWorked = collect($attendanceData['daily'])
        ->filter(function ($day) {
          return $day['type'] === 'work' && !empty($day['check_in']);
        })
        ->count();

      // Horas estándar: 8h × días con check_in
      return $daysWorked * 8;
    } catch (\Exception $e) {
      // Si falla, retornar 0
      return 0;
    }
  }

  /**
   * Obtiene los datos completos de asistencia para un técnico
   * MÉTODO CENTRALIZADO usado por servicios que necesitan tanto horas estándar como reales
   *
   * @param int $workerId ID del técnico
   * @param string $startDate Fecha inicio (Y-m-d)
   * @param string $endDate Fecha fin (Y-m-d)
   * @return array ['standard_hours' => float, 'real_hours' => float, 'days_worked' => int, 'attendance_data' => array]
   */
  public function getAttendanceData(int $workerId, string $startDate, string $endDate): array
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

      // Contar días con check_in
      $daysWorked = collect($attendanceData['daily'])
        ->filter(function ($day) {
          return $day['type'] === 'work' && !empty($day['check_in']);
        })
        ->count();

      // Verificar días programados en campaña (fuera de su centro laboral)
      $daysInCampaign = $this->countCampaignDaysWithoutAttendance(
        $workerId,
        $startDate,
        $endDate,
        $attendanceData['daily'] ?? []
      );

      // Total de días trabajados: días con check_in + días en campaña sin asistencia
      $totalDaysWorked = $daysWorked + $daysInCampaign;

      // Horas estándar: 8h × total de días trabajados
      $standardHours = $totalDaysWorked * 8;

      // Horas reales: Parsear del formato "XXXh YYmin"
      $realHours = $this->parseHoursFromString($attendanceData['hours_worked']);

      return [
        'standard_hours' => $standardHours,
        'real_hours' => $realHours,
        'days_worked' => $totalDaysWorked,
        'attendance_data' => $attendanceData, // Datos completos por si se necesitan
      ];
    } catch (\Exception $e) {
      // Si falla, retornar valores en 0
      return [
        'standard_hours' => 0,
        'real_hours' => 0,
        'days_worked' => 0,
        'attendance_data' => [],
      ];
    }
  }

  /**
   * Cuenta días programados en campaña (fuera de su centro laboral) sin asistencia registrada
   *
   * @param int $workerId ID del técnico
   * @param string $startDate Fecha inicio (Y-m-d)
   * @param string $endDate Fecha fin (Y-m-d)
   * @param array $attendanceDaily Array de días de asistencia del trabajador
   * @return int Cantidad de días en campaña sin asistencia
   */
  private function countCampaignDaysWithoutAttendance(int $workerId, string $startDate, string $endDate, array $attendanceDaily): int
  {
    try {
      // Obtener el trabajador para tener su sede_id
      $worker = Worker::find($workerId);

      if (!$worker || !$worker->sede_id) {
        return 0;
      }

      // Crear colección de días de asistencia indexada por fecha
      $attendanceDays = collect($attendanceDaily)->keyBy('date');

      // Generar todas las fechas del rango
      $start = new \DateTime($startDate);
      $end = new \DateTime($endDate);
      $interval = new \DateInterval('P1D');
      $dateRange = new \DatePeriod($start, $interval, $end->modify('+1 day'));

      $campaignDaysCount = 0;

      foreach ($dateRange as $date) {
        $dateString = $date->format('Y-m-d');

        // Verificar si el día tiene asistencia registrada
        $dayAttendance = $attendanceDays->get($dateString);

        // Si NO tiene check_in o no existe el día de tipo work
        $hasCheckIn = $dayAttendance &&
                      isset($dayAttendance['type']) &&
                      $dayAttendance['type'] === 'work' &&
                      !empty($dayAttendance['check_in']);

        if (!$hasCheckIn) {
          // Consultar si existe registro en ApCampaignSchedule para este día
          $campaignSchedule = ApCampaignSchedule::where('worker_id', $workerId)
            ->where('sede_id', $worker->sede_id)
            ->where('date', $dateString)
            ->first();

          if ($campaignSchedule) {
            $campaignDaysCount++;
          }
        }
      }

      return $campaignDaysCount;
    } catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Parsea horas desde el formato string "XXXh YYmin" a decimal
   *
   * @param string $hoursString Ejemplo: "246h 11min"
   * @return float Horas en formato decimal
   */
  private function parseHoursFromString(string $hoursString): float
  {
    // Formato: "246h 11min"
    preg_match('/(\d+)h(?: (\d+)min)?/', $hoursString, $matches);

    $hours = isset($matches[1]) ? (int)$matches[1] : 0;
    $minutes = isset($matches[2]) ? (int)$matches[2] : 0;

    return $hours + ($minutes / 60);
  }
}
