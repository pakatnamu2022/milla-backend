<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\postventa\taller\ApWorkOrderPlanning;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WorkedHoursBySedeReportService
{
  /**
   * Obtiene el reporte de Horas Trabajadas por Sede
   *
   * @param array $filters
   * @return array ['summary' => Collection, 'detail' => Collection]
   */
  public function getWorkedHoursBySedeReport(array $filters = []): array
  {
    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->getUserSedeIds();

    // Consultar ApWorkOrderPlanning con status 'completed'
    $query = ApWorkOrderPlanning::query()
      ->with([
        'worker',
        'workOrder.sede',
        'workOrder.items.typePlanning'
      ])
      ->where('status', 'completed');

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $query->whereHas('workOrder', function ($q) use ($userSedeIds) {
        $q->whereIn('sede_id', $userSedeIds);
      });
    }

    // Aplicar filtros
    foreach ($filters as $filter) {
      $column = $filter['column'] ?? null;
      $operator = $filter['operator'] ?? '=';
      $value = $filter['value'] ?? null;

      if (!$column || $value === null) {
        continue;
      }

      if ($column === 'sede_id' && $operator === '=') {
        $query->whereHas('workOrder', function ($q) use ($value) {
          $q->where('sede_id', $value);
        });
      } elseif ($column === 'actual_end_datetime' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          // Filtrar por DATE (sin horas) usando DATE()
          $query->whereRaw('DATE(actual_end_datetime) BETWEEN ? AND ?', [$value[0], $value[1]]);
        }
      }
    }

    $plannings = $query->get();

    // Generar datos detallados
    $detailData = $this->generateDetailData($plannings);

    // Agrupar por sede y técnico
    $groupedBySede = $plannings->groupBy(function ($planning) {
      return $planning->workOrder->sede_id ?? 'SIN_SEDE';
    });

    $reportData = collect();

    // Procesar cada sede
    foreach ($groupedBySede as $sedeId => $sedePlannings) {
      // Agrupar por técnico
      $groupedByWorker = $sedePlannings->groupBy('worker_id');

      foreach ($groupedByWorker as $workerId => $workerPlannings) {
        $worker = $workerPlannings->first()->worker;

        if (!$worker) {
          continue;
        }

        // Calcular horas por categoría
        $horasInterna = 0;
        $horasEstandar = 0;
        $horasGarantiaRecall = 0;

        foreach ($workerPlannings as $planning) {
          // Obtener el primer item no eliminado de la orden de trabajo
          $workOrderItem = $planning->workOrder->items->first();

          if (!$workOrderItem || !$workOrderItem->typePlanning) {
            continue;
          }

          $categoryType = $workOrderItem->typePlanning->category_type;
          $actualHours = (float)$planning->actual_hours;

          switch ($categoryType) {
            case TypePlanningWorkOrder::INTERNA:
              $horasInterna += $actualHours;
              break;
            case TypePlanningWorkOrder::ESTANDAR:
              $horasEstandar += $actualHours;
              break;
            case TypePlanningWorkOrder::GARANTIA_RECALL:
              $horasGarantiaRecall += $actualHours;
              break;
          }
        }

        $sede = $sedePlannings->first()->workOrder->sede;

        $reportData->push([
          'sede' => $sede ? $sede->abreviatura : 'SIN SEDE',
          'sede_id' => $sedeId,
          'dni_tecnico' => $worker->vat ?? '',
          'nombre_tecnico' => $worker->nombre_completo ?? '',
          'horas_interna' => number_format($horasInterna, 2, '.', ''),
          'horas_estandar' => number_format($horasEstandar, 2, '.', ''),
          'horas_garantia_recall' => number_format($horasGarantiaRecall, 2, '.', ''),
          'total_horas' => number_format($horasInterna + $horasEstandar + $horasGarantiaRecall, 2, '.', ''),
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
    $totalGeneral = $totalInterna + $totalEstandar + $totalGarantiaRecall;

    // Agregar fila de totales al final
    $reportData->push([
      'sede' => 'TOTAL GENERAL',
      'sede_id' => null,
      'dni_tecnico' => '',
      'nombre_tecnico' => '',
      'horas_interna' => number_format($totalInterna, 2, '.', ''),
      'horas_estandar' => number_format($totalEstandar, 2, '.', ''),
      'horas_garantia_recall' => number_format($totalGarantiaRecall, 2, '.', ''),
      'total_horas' => number_format($totalGeneral, 2, '.', ''),
    ]);

    // Obtener horas facturadas
    $billedData = $this->getBilledHoursBySedeReport($filters, $userSedeIds);

    // Obtener detalle de horas facturadas
    $billedDetailData = $this->getBilledHoursDetailData($filters, $userSedeIds);

    return [
      'summary' => $reportData,
      'detail' => $detailData,
      'billed' => $billedData,
      'billed_detail' => $billedDetailData
    ];
  }

  /**
   * Genera los datos detallados de cada planificación
   *
   * @param Collection $plannings
   * @return Collection
   */
  private function generateDetailData(Collection $plannings): Collection
  {
    $detailData = collect();

    foreach ($plannings as $planning) {
      $workOrder = $planning->workOrder;
      $worker = $planning->worker;
      $workOrderItem = $workOrder->items->first();

      if (!$workOrderItem || !$workOrderItem->typePlanning) {
        continue;
      }

      $detailData->push([
        'sede' => $workOrder->sede ? $workOrder->sede->abreviatura : 'SIN SEDE',
        'numero_ot' => $workOrder->correlative ?? '',
        'dni_tecnico' => $worker ? $worker->vat : '',
        'nombre_tecnico' => $worker ? $worker->nombre_completo : '',
        'tipo_planificacion' => $workOrderItem->typePlanning->description ?? '',
        'categoria_tipo' => $workOrderItem->typePlanning->category_type ?? '',
        'descripcion_item' => $workOrderItem->description ?? '',
        'horas_trabajadas' => number_format((float)$planning->actual_hours, 2, '.', ''),
        'fecha_inicio' => $planning->actual_start_datetime ? $planning->actual_start_datetime->format('d/m/Y H:i') : '',
        'fecha_finalizacion' => $planning->actual_end_datetime ? $planning->actual_end_datetime->format('d/m/Y H:i') : '',
      ]);
    }

    // Ordenar por sede, número de OT
    return $detailData->sortBy([
      ['sede', 'asc'],
      ['numero_ot', 'asc']
    ])->values();
  }

  /**
   * Obtiene el reporte de Horas Facturadas por Sede
   * Distribuye las horas facturadas en partes iguales entre los técnicos que trabajaron en la OT
   *
   * @param array $filters
   * @param array $userSedeIds
   * @return Collection
   */
  private function getBilledHoursBySedeReport(array $filters, array $userSedeIds): Collection
  {
    // Primero obtener los IDs de work orders que tienen planificaciones completadas
    $workOrderIdsQuery = ApWorkOrderPlanning::query()
      ->where('status', 'completed')
      ->select('work_order_id');

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $workOrderIdsQuery->whereHas('workOrder', function ($q) use ($userSedeIds) {
        $q->whereIn('sede_id', $userSedeIds);
      });
    }

    // Aplicar filtros de fecha
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

    // Consultar WorkOrderLabour con las exclusiones usando labour_type
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

        $reportData->push([
          'sede' => $sede ? $sede->abreviatura : 'SIN SEDE',
          'sede_id' => $sedeId,
          'dni_tecnico' => $worker->vat ?? '',
          'nombre_tecnico' => $worker->nombre_completo ?? '',
          'horas_interna' => number_format($horasInterna, 2, '.', ''),
          'horas_estandar' => number_format($horasEstandar, 2, '.', ''),
          'horas_garantia_recall' => number_format($horasGarantiaRecall, 2, '.', ''),
          'total_horas' => number_format($horasInterna + $horasEstandar + $horasGarantiaRecall, 2, '.', ''),
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
    $totalGeneral = $totalInterna + $totalEstandar + $totalGarantiaRecall;

    // Agregar fila de totales al final
    $reportData->push([
      'sede' => 'TOTAL GENERAL',
      'sede_id' => null,
      'dni_tecnico' => '',
      'nombre_tecnico' => '',
      'horas_interna' => number_format($totalInterna, 2, '.', ''),
      'horas_estandar' => number_format($totalEstandar, 2, '.', ''),
      'horas_garantia_recall' => number_format($totalGarantiaRecall, 2, '.', ''),
      'total_horas' => number_format($totalGeneral, 2, '.', ''),
    ]);

    return $reportData;
  }

  /**
   * Genera los datos detallados de cada facturación por OT
   * Muestra cómo se distribuyen las horas facturadas entre los técnicos
   *
   * @param array $filters
   * @param array $userSedeIds
   * @return Collection
   */
  private function getBilledHoursDetailData(array $filters, array $userSedeIds): Collection
  {
    // Primero obtener los IDs de work orders que tienen planificaciones completadas
    $workOrderIdsQuery = ApWorkOrderPlanning::query()
      ->where('status', 'completed')
      ->select('work_order_id');

    // Filtrar por sedes del usuario
    if (!empty($userSedeIds)) {
      $workOrderIdsQuery->whereHas('workOrder', function ($q) use ($userSedeIds) {
        $q->whereIn('sede_id', $userSedeIds);
      });
    }

    // Aplicar filtros de fecha
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

    // Consultar WorkOrderLabour con las exclusiones usando labour_type
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
