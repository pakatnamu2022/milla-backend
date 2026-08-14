<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ClosedWorkOrderBilledHoursReportService
{
  /**
   * Obtiene el reporte de Horas Facturadas de Órdenes de Trabajo Cerradas
   *
   * @param array $filters
   * @return array ['summary' => Collection, 'detail' => Collection]
   */
  public function getClosedWorkOrderBilledHoursReport(array $filters = []): array
  {
    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->getUserSedeIds();

    // Consultar órdenes de trabajo CERRADAS
    $queryWorkOrders = ApWorkOrder::query()
      ->with([
        'sede',
        'items.typePlanning',
        'plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        }
      ])
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID); // Solo OTs cerradas

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $queryWorkOrders->whereIn('sede_id', $userSedeIds);
    }

    // Aplicar filtros adicionales (sede, fecha)
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      if ($column === 'sede_id' && $operator === '=') {
        $queryWorkOrders->where('sede_id', $value);
      } elseif ($column === 'actual_end_datetime' && $operator === 'date_between') {
        // Filtrar por fecha de finalización de las planificaciones
        if (is_array($value) && count($value) === 2) {
          $queryWorkOrders->whereHas('plannings', function ($q) use ($value) {
            $q->where('status', 'completed')
              ->whereRaw('DATE(actual_end_datetime) BETWEEN ? AND ?', [$value[0], $value[1]]);
          });
        }
      }
    }

    $workOrderIds = $queryWorkOrders->pluck('id')->toArray();

    if (empty($workOrderIds)) {
      return [
        'summary' => collect(),
        'detail' => collect(),
      ];
    }

    // Consultar WorkOrderLabour de las OTs cerradas
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

    // Generar resumen de horas facturadas
    $summaryData = $this->generateBilledHoursSummary($labours);

    // Generar detalle de horas facturadas
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
      $billedHours = $labour->time_spent_decimal; // Horas facturadas al cliente
      $sedeId = $workOrder->sede_id ?? 'SIN_SEDE';

      // Obtener todos los técnicos que trabajaron en esta OT
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      // Contar el número de técnicos que trabajaron en esta OT
      $totalWorkers = $plannings->count();

      // Si no hay técnicos, no podemos distribuir
      if ($totalWorkers <= 0) {
        continue;
      }

      // Distribuir las horas facturadas en partes iguales entre los técnicos
      foreach ($plannings as $planning) {
        $worker = $planning->worker;

        if (!$worker) {
          continue;
        }

        // Calcular la distribución igual: horas facturadas / número de técnicos
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

        // Horas estándar fijas: 8 horas × 6 días × 4 semanas = 192
        $horasEstandarFijas = 192;

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

    // Ordenar por sede y luego por nombre de técnico
    $reportData = $reportData->sortBy([
      ['sede', 'asc'],
      ['nombre_tecnico', 'asc']
    ])->values();

    // Calcular acumulado total
    $totalInterna = $reportData->sum(fn($row) => (float)$row['horas_interna']);
    $totalEstandar = $reportData->sum(fn($row) => (float)$row['horas_estandar']);
    $totalGarantiaRecall = $reportData->sum(fn($row) => (float)$row['horas_garantia_recall']);
    $totalHorasFacturadas = $totalInterna + $totalEstandar + $totalGarantiaRecall;

    // Total de técnicos (excluyendo la fila de totales)
    $cantidadTecnicos = $reportData->count();
    $totalHorasEstandarFijas = $cantidadTecnicos * 192;
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
      $billedHours = $labour->time_spent_decimal; // Horas facturadas al cliente
      $labourDescription = $labour->description ?? ''; // Descripción del labour
      $sede = $workOrder->sede ? $workOrder->sede->abreviatura : 'SIN SEDE';
      $numeroOT = $workOrder->correlative ?? '';

      // Obtener todos los técnicos que trabajaron en esta OT
      $plannings = $workOrder->plannings;

      if ($plannings->isEmpty()) {
        continue;
      }

      // Contar el número de técnicos y calcular distribución
      $totalWorkers = $plannings->count();
      $equalBilledHours = $billedHours / $totalWorkers;

      // Generar una fila por cada técnico que trabajó en la OT
      foreach ($plannings as $planning) {
        $worker = $planning->worker;

        if (!$worker) {
          continue;
        }

        $detailData->push([
          'sede' => $sede,
          'numero_ot' => $numeroOT,
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
   * Obtiene los IDs de las sedes asociadas al usuario autenticado
   *
   * @return array
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