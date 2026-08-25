<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClosedWorkOrderBilledHoursReportService
{
  private ?string $startDate = null;
  private ?string $endDate = null;

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

    // Obtener sedes del usuario autenticado
    $userSedeIds = $this->getUserSedeIds();

    // Collect all work orders using the same logic as WorkShopReportService
    $workOrders = collect();

    // 1. Get work orders from electronic documents (SIMPLE and MASSIVE invoicing)
    $queryDocuments = ElectronicDocument::query()
      ->with([
        'workOrder.sede',
        'workOrder.items.typePlanning',
        'workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        },
        'internalNotes.workOrder.sede',
        'internalNotes.workOrder.items.typePlanning',
        'internalNotes.workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        }
      ])
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false) // Only final invoices
      ->where(function ($q) {
        $q->whereNotNull('work_order_id')
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Filter by user sedes
    if (!empty($userSedeIds)) {
      $queryDocuments->where(function ($q) use ($userSedeIds) {
        $q->whereHas('workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        });
      });
    }

    // Filter by fecha_de_emision (invoice date)
    if ($startDate && $endDate) {
      $queryDocuments->whereBetween('fecha_de_emision', [$startDate, $endDate]);
    }

    // Filter by sede if specified
    if ($sedeId) {
      $queryDocuments->where(function ($q) use ($sedeId) {
        $q->whereHas('workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        });
      });
    }

    $documents = $queryDocuments->get();

    // Extract work orders from documents
    foreach ($documents as $document) {
      // SIMPLE invoicing
      if ($document->workOrder) {
        // Filter by sede if specified
        if (!$sedeId || $document->workOrder->sede_id == $sedeId) {
          $workOrders->push($document->workOrder);
        }
      }

      // MASSIVE invoicing
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        foreach ($document->internalNotes as $internalNote) {
          if ($internalNote->workOrder) {
            // Filter by sede if specified
            if (!$sedeId || $internalNote->workOrder->sede_id == $sedeId) {
              $workOrders->push($internalNote->workOrder);
            }
          }
        }
      }
    }

    // 2. Get work orders with internal note WITHOUT invoice
    $queryInternalNoteWorkOrders = ApWorkOrder::query()
      ->with([
        'sede',
        'items.typePlanning',
        'plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        },
        'internalNotes'
      ])
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->whereHas('internalNotes', function ($q) {
        $q->whereNotNull('number');
      })
      ->whereHas('items', function ($q) {
        $q->whereHas('typePlanning', function ($subQ) {
          $subQ->whereIn('type_document', [
            TypePlanningWorkOrder::INTERNA_SC,
            TypePlanningWorkOrder::INTERNA_CC,
          ])
            ->whereNotIn('id', [
              TypePlanningWorkOrder::TYPE_PLANNING_DERCO_WARRANTY_ID,
              TypePlanningWorkOrder::TYPE_PLANNING_ODEBRECHT_MAINTENANCE,
            ]);
        });
      })
      ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('ap_billing_electronic_documents')
          ->whereColumn('ap_billing_electronic_documents.work_order_id', 'ap_work_orders.id')
          ->where('ap_billing_electronic_documents.anulado', false);
      })
      ->whereDoesntHave('internalNotes', function ($q) {
        $q->whereHas('electronicDocuments');
      });

    // Filter by user sedes
    if (!empty($userSedeIds)) {
      $queryInternalNoteWorkOrders->whereIn('sede_id', $userSedeIds);
    }

    // Filter by sede if specified
    if ($sedeId) {
      $queryInternalNoteWorkOrders->where('sede_id', $sedeId);
    }

    // Filter by internal note created_date
    if ($startDate && $endDate) {
      $queryInternalNoteWorkOrders->whereHas('internalNotes', function ($q) use ($startDate, $endDate) {
        $q->whereBetween('created_date', [$startDate, $endDate]);
      });
    }

    $internalNoteWorkOrders = $queryInternalNoteWorkOrders->get();
    $workOrders = $workOrders->merge($internalNoteWorkOrders);

    // Remove duplicates by work order ID
    $workOrders = $workOrders->unique('id');

    $workOrderIds = $workOrders->pluck('id')->toArray();

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
      $billedHours = $labour->time_spent_decimal; // Horas facturadas al cliente
      $labourDescription = $labour->description ?? ''; // Descripción del labour
      $sede = $workOrder->sede ? $workOrder->sede->abreviatura : 'SIN SEDE';
      $numeroOT = $workOrder->correlative ?? '';
      $TypePlanningDescription = $workOrderItem->typePlanning->description ?? '';

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
