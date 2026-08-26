<?php

namespace App\Http\Services\ap\postventa\Shared;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para el cálculo de horas facturadas
 * Usado por ProductivityDashboardService, TechnicianProductivityDetailService y ClosedWorkOrderBilledHoursReportService
 */
class BilledHoursCalculationService
{
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

      // Calcular horas facturadas equivalentes: (hourly_rate * time_spent) / current_hourly_cost
      $billedHours = $labour->current_hourly_cost > 0
        ? ($labour->hourly_rate * $labour->time_spent_decimal) / $labour->current_hourly_cost
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
   * @return array ['total_billed_hours' => float, 'work_orders_detail' => array, 'work_orders_without_labour' => array]
   */
  public function calculateBilledHoursForWorker(Collection $labours, int $workerId, Collection $workOrders): array
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

      // Calcular horas facturadas equivalentes
      $billedHours = $labour->current_hourly_cost > 0
        ? ($labour->hourly_rate * $labour->time_spent_decimal) / $labour->current_hourly_cost
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

      // Obtener fecha de facturación
      $invoiceDate = $this->getInvoiceDate($workOrder);

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
        $invoiceDate = $this->getInvoiceDate($workOrder);

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
   *
   * @param string $startDate
   * @param string $endDate
   * @param int|null $sedeId
   * @param array $userSedeIds
   * @return Collection
   */
  public function getWorkOrders(string $startDate, string $endDate, ?int $sedeId, array $userSedeIds): Collection
  {
    $workOrders = collect();

    // 1. Get work orders from electronic documents (SIMPLE and MASSIVE invoicing)
    $queryDocuments = ElectronicDocument::query()
      ->with([
        'workOrder.sede',
        'workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        },
        'workOrder.items.typePlanning',
        'internalNotes.workOrder.sede',
        'internalNotes.workOrder.plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        },
        'internalNotes.workOrder.items.typePlanning'
      ])
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false)
      ->where(function ($q) {
        $q->whereNotNull('work_order_id')
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Filter by user sedes (solo si userSedeIds no está vacío)
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
    $queryDocuments->whereBetween('fecha_de_emision', [$startDate, $endDate]);

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
        if (!$sedeId || $document->workOrder->sede_id == $sedeId) {
          $workOrders->push($document->workOrder);
        }
      }

      // MASSIVE invoicing
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        foreach ($document->internalNotes as $internalNote) {
          if ($internalNote->workOrder) {
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
        'plannings' => function ($query) {
          $query->where('status', 'completed')->whereNotNull('worker_id')->with('worker');
        },
        'internalNotes',
        'items.typePlanning'
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

    // Filter by user sedes (solo si userSedeIds no está vacío)
    if (!empty($userSedeIds)) {
      $queryInternalNoteWorkOrders->whereIn('sede_id', $userSedeIds);
    }

    // Filter by sede if specified
    if ($sedeId) {
      $queryInternalNoteWorkOrders->where('sede_id', $sedeId);
    }

    // Filter by internal note created_date
    $queryInternalNoteWorkOrders->whereHas('internalNotes', function ($q) use ($startDate, $endDate) {
      $q->whereBetween('created_date', [$startDate, $endDate]);
    });

    $internalNoteWorkOrders = $queryInternalNoteWorkOrders->get();
    $workOrders = $workOrders->merge($internalNoteWorkOrders);

    // Remove duplicates by work order ID
    $workOrders = $workOrders->unique('id');

    return $workOrders;
  }

  /**
   * Obtiene la fecha de facturación de una work order
   *
   * @param ApWorkOrder $workOrder
   * @return string
   */
  private function getInvoiceDate(ApWorkOrder $workOrder): string
  {
    // Try to get from electronic document
    $electronicDocument = ElectronicDocument::query()
      ->where('work_order_id', $workOrder->id)
      ->where('anulado', false)
      ->first();

    if ($electronicDocument) {
      return $electronicDocument->fecha_de_emision;
    }

    // Try to get from internal note
    $internalNote = $workOrder->internalNotes()->whereNotNull('number')->first();
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
}
