<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Http\Services\ap\postventa\Shared\BilledHoursCalculationService;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Support\Collection;

class ClosedWorkOrderBilledHoursReportService
{
  protected BilledHoursCalculationService $billedHoursService;
  private ?string $startDate = null;
  private ?string $endDate = null;

  public function __construct(BilledHoursCalculationService $billedHoursService)
  {
    $this->billedHoursService = $billedHoursService;
  }

  /**
   * Obtiene el reporte de Horas Facturadas de Órdenes de Trabajo Cerradas
   *
   * @param array $filters
   * @return array ['summary' => Collection, 'detail' => Collection]
   */
  public function getClosedWorkOrderBilledHoursReport(array $filters = []): array
  {
    // Extract date range and sede_id from filters
    $startDate = null;
    $endDate = null;
    $sedeId = null;

    foreach ($filters as $filter) {
      if (($filter['column'] ?? null) === 'actual_end_datetime' && ($filter['operator'] ?? null) === 'date_between') {
        $startDate = $filter['value'][0] ?? null;
        $endDate = $filter['value'][1] ?? null;
      }
      if (($filter['column'] ?? null) === 'sede_id' && ($filter['operator'] ?? null) === '=') {
        $sedeId = $filter['value'] ?? null;
      }
    }

    // Store period dates for standard hours calculation
    $this->startDate = $startDate;
    $this->endDate = $endDate;

    // Obtener sedes del usuario autenticado usando el servicio centralizado
    $userSedeIds = $this->billedHoursService->getUserSedeIds();

    // Get labours usando el servicio centralizado
    $labours = $this->billedHoursService->getBilledHoursData($startDate, $endDate, $sedeId, $userSedeIds);

    // Generar resumen y detalle de horas facturadas
    $summaryData = $this->generateBilledHoursSummary($labours);
    $detailData = $this->generateBilledHoursDetail($labours);

    return [
      'summary' => $summaryData,
      'detail' => $detailData,
    ];
  }

  /**
   * Genera el resumen de horas facturadas agrupadas por sede y técnico
   *
   * @param Collection $labours
   * @return Collection
   */
  private function generateBilledHoursSummary(Collection $labours): Collection
  {
    // Estructura para acumular horas por técnico: [sede_id][worker_id][category_type] = horas
    $workerHours = [];

    // Procesar cada labour (horas facturadas)
    foreach ($labours as $labour) {
      $workOrder = $labour->workOrder;
      $workOrderItem = $workOrder->items->first();

      if (!$workOrderItem || !$workOrderItem->typePlanning) {
        continue;
      }

      $categoryType = $workOrderItem->typePlanning->category_type;

      // Calculate equivalent billed hours: (hourly_rate * time_spent) / current_hourly_cost
      // This normalizes all hours to the same standard cost
      $billedHours = $labour->current_hourly_cost > 0
        ? ($labour->hourly_rate * $labour->time_spent_decimal) / $labour->current_hourly_cost
        : 0;

      $sedeId = $workOrder->sede_id ?? 'SIN_SEDE';

      // Obtener todos los técnicos que trabajaron en esta OT
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      // Consolidar técnicos ÚNICOS (ignorar planificaciones duplicadas del mismo técnico)
      $uniqueWorkers = $plannings->unique('worker_id');
      $totalWorkers = $uniqueWorkers->count();

      // Si no hay técnicos, no podemos distribuir
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
            TypePlanningWorkOrder::INTERNA => 0,
            TypePlanningWorkOrder::ESTANDAR => 0,
            TypePlanningWorkOrder::GARANTIA_RECALL => 0,
          ];
        }

        // Acumular las horas en partes iguales por categoría
        $workerHours[$sedeId][$worker->id][$categoryType] += $equalBilledHours;
      }
    }

    // Convertir la estructura a Collection para el reporte
    $reportData = collect();

    foreach ($workerHours as $sedeId => $workers) {
      foreach ($workers as $workerId => $data) {
        $worker = $data['worker'];
        $sede = $data['sede'];

        $horasInterna = $data[TypePlanningWorkOrder::INTERNA];
        $horasEstandar = $data[TypePlanningWorkOrder::ESTANDAR];
        $horasGarantiaRecall = $data[TypePlanningWorkOrder::GARANTIA_RECALL];
        $totalHorasFacturadas = $horasInterna + $horasEstandar + $horasGarantiaRecall;

        // Calcular horas estándar dinámicamente basadas en asistencias
        $horasEstandarFijas = $this->calculateStandardHours($workerId);

        // Costo por hora fijo
        $costoPorHora = 8;

        // Horas de productividad: Total facturado - Horas estándar
        $horasProductividad = $totalHorasFacturadas - $horasEstandarFijas;

        // Porcentaje de productividad: (Total facturado / Horas estándar) * 100
        $porcentajeProductividad = $horasEstandarFijas > 0
          ? ($totalHorasFacturadas / $horasEstandarFijas) * 100
          : 0;

        // Comisión: Solo horas de productividad positivas × costo por hora
        $comision = max(0, $horasProductividad) * $costoPorHora;

        $reportData->push([
          'sede' => $sede ? $sede->abreviatura : 'SIN SEDE',
          'sede_id' => $sedeId,
          'dni_tecnico' => $worker->vat ?? '',
          'nombre_tecnico' => $worker->nombre_completo ?? '',
          'horas_interna' => number_format($horasInterna, 2, '.', ''),
          'horas_estandar' => number_format($horasEstandar, 2, '.', ''),
          'horas_garantia_recall' => number_format($horasGarantiaRecall, 2, '.', ''),
          'total_horas' => number_format($totalHorasFacturadas, 2, '.', ''),
          'horas_estandar_fijas' => number_format($horasEstandarFijas, 2, '.', ''),
          'costo_por_hora' => number_format($costoPorHora, 2, '.', ''),
          'horas_productividad' => number_format($horasProductividad, 2, '.', ''),
          'porcentaje_productividad' => number_format($porcentajeProductividad, 2, '.', ''),
          'comision' => number_format($comision, 2, '.', ''),
        ]);
      }
    }

    // Ordenar por porcentaje de productividad de mayor a menor
    $reportData = $reportData->sortByDesc(function ($row) {
      return (float)$row['porcentaje_productividad'];
    })->values();

    // Calcular acumulado total
    $totalInterna = $reportData->sum(fn($row) => (float)$row['horas_interna']);
    $totalEstandar = $reportData->sum(fn($row) => (float)$row['horas_estandar']);
    $totalGarantiaRecall = $reportData->sum(fn($row) => (float)$row['horas_garantia_recall']);
    $totalHorasFacturadas = $totalInterna + $totalEstandar + $totalGarantiaRecall;

    // Total de horas estándar: sumar las horas estándar de cada técnico
    $totalHorasEstandarFijas = $reportData->sum(fn($row) => (float)$row['horas_estandar_fijas']);
    $totalHorasProductividad = $totalHorasFacturadas - $totalHorasEstandarFijas;

    // Porcentaje total de productividad
    $porcentajeTotalProductividad = $totalHorasEstandarFijas > 0
      ? ($totalHorasFacturadas / $totalHorasEstandarFijas) * 100
      : 0;

    // Comisión total: Sumar las comisiones de todos los técnicos
    $totalComision = $reportData->sum(fn($row) => (float)$row['comision']);

    // Agregar fila de totales al final
    $reportData->push([
      'sede' => 'TOTAL GENERAL',
      'sede_id' => null,
      'dni_tecnico' => '',
      'nombre_tecnico' => '',
      'horas_interna' => number_format($totalInterna, 2, '.', ''),
      'horas_estandar' => number_format($totalEstandar, 2, '.', ''),
      'horas_garantia_recall' => number_format($totalGarantiaRecall, 2, '.', ''),
      'total_horas' => number_format($totalHorasFacturadas, 2, '.', ''),
      'horas_estandar_fijas' => number_format($totalHorasEstandarFijas, 2, '.', ''),
      'costo_por_hora' => '8.00',
      'horas_productividad' => number_format($totalHorasProductividad, 2, '.', ''),
      'porcentaje_productividad' => number_format($porcentajeTotalProductividad, 2, '.', ''),
      'comision' => number_format($totalComision, 2, '.', ''),
    ]);

    return $reportData;
  }

  /**
   * Genera los datos detallados de cada facturación por OT
   * Muestra cómo se distribuyen las horas facturadas entre los técnicos
   *
   * @param Collection $labours
   * @return Collection
   */
  private function generateBilledHoursDetail(Collection $labours): Collection
  {
    $detailData = collect();

    // Procesar cada labour (horas facturadas)
    foreach ($labours as $labour) {
      $workOrder = $labour->workOrder;
      $workOrderItem = $workOrder->items->first();

      if (!$workOrderItem || !$workOrderItem->typePlanning) {
        continue;
      }

      $categoryType = $workOrderItem->typePlanning->category_type;

      // Calculate equivalent billed hours: (hourly_rate * time_spent) / current_hourly_cost
      // This normalizes all hours to the same standard cost
      $billedHours = $labour->current_hourly_cost > 0
        ? ($labour->hourly_rate * $labour->time_spent_decimal) / $labour->current_hourly_cost
        : 0;

      $labourDescription = $labour->description ?? ''; // Descripción del labour
      $sede = $workOrder->sede ? $workOrder->sede->abreviatura : 'SIN SEDE';
      $numeroOT = $workOrder->correlative ?? '';
      $TypePlanningDescription = $workOrderItem->typePlanning->description ?? '';

      // Obtener todos los técnicos que trabajaron en esta OT
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      // Consolidar técnicos ÚNICOS (ignorar planificaciones duplicadas del mismo técnico)
      $uniqueWorkers = $plannings->unique('worker_id');
      $totalWorkers = $uniqueWorkers->count();
      $equalBilledHours = $billedHours / $totalWorkers;

      // Generar una fila por cada técnico ÚNICO que trabajó en la OT
      foreach ($uniqueWorkers as $planning) {
        $worker = $planning->worker;

        if (!$worker) {
          continue;
        }

        $detailData->push([
          'sede' => $sede,
          'numero_ot' => $numeroOT,
          'tipo_planificacion' => $TypePlanningDescription,
          'descripcion_labour' => $labourDescription,
          'categoria_tipo' => $categoryType,
          'horas_facturadas_total' => number_format($billedHours, 2, '.', ''),
          'cantidad_tecnicos' => $totalWorkers,
          'dni_tecnico' => $worker->vat ?? '',
          'nombre_tecnico' => $worker->nombre_completo ?? '',
          'horas_trabajadas' => number_format((float)$planning->actual_hours, 2, '.', ''),
          'horas_asignadas' => number_format($equalBilledHours, 2, '.', ''),
        ]);
      }
    }

    // Ordenar por sede, número de OT y nombre de técnico
    return $detailData->sortBy([
      ['sede', 'asc'],
      ['numero_ot', 'asc'],
      ['nombre_tecnico', 'asc']
    ])->values();
  }

  /**
   * Calcula las horas estándar para un técnico basado en sus asistencias
   *
   * @param int $workerId
   * @return float
   */
  private function calculateStandardHours(int $workerId): float
  {
    // Si no hay rango de fechas, usar el valor fijo anterior
    if (!$this->startDate || !$this->endDate) {
      return 192;
    }

    try {
      $attendanceService = new \App\Http\Services\gp\gestionhumana\asistencias\AttendanceSyncService();

      $attendanceRequest = new \Illuminate\Http\Request([
        'date_from' => $this->startDate,
        'date_to' => $this->endDate,
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
      // Si falla, usar el valor fijo anterior
      return 192;
    }
  }

  }
