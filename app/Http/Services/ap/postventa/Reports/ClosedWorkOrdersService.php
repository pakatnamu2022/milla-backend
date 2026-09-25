<?php

namespace App\Http\Services\ap\postventa\Reports;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use App\Models\gp\gestionsistema\UserSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para obtener Órdenes de Trabajo Cerradas
 *
 * Define "OT Cerrada" como aquellas que tienen un comprobante final válido aceptado.
 * Esto NO es lo mismo que status_id = CLOSED_WORK_ORDER_ID.
 *
 * Cubre 3 escenarios de cierre:
 *
 * 1. FACTURACIÓN SIMPLE:
 *    - ElectronicDocument con work_order_id directo
 *    - is_advance_payment = false (comprobante final)
 *    - status IN (SENT, ACCEPTED) y anulado = false
 *
 * 2. FACTURACIÓN MASIVA:
 *    - ElectronicDocument con internal_notes (facturadas)
 *    - Cada nota interna apunta a una work_order_id
 *    - Un documento agrupa varias OTs
 *
 * 3. NOTA INTERNA SIN FACTURA (INTERNA_SC, INTERNA_CC):
 *    - OT con status_id = CLOSED_WORK_ORDER_ID
 *    - Tiene nota interna con número generado
 *    - NO tiene documento electrónico asociado
 *    - Tipo de planificación INTERNA_SC o INTERNA_CC
 */
class ClosedWorkOrdersService
{
  /**
   * Obtiene las Órdenes de Trabajo cerradas según el rango de fechas
   *
   * IMPORTANTE: La fecha de cierre se toma de:
   * - fecha_de_emision del comprobante (casos 1 y 2)
   * - created_date de la nota interna (caso 3)
   *
   * @param array $dateRange Array con [fecha_inicio, fecha_fin]
   * @param int|null $sedeId Filtro opcional por sede
   * @param array|null $userSedeIds IDs de sedes del usuario autenticado (opcional, si null se obtienen automáticamente)
   * @return Collection Collection de ApWorkOrder
   */
  public function getClosedWorkOrders(
    array $dateRange,
    ?int $sedeId = null,
    ?array $userSedeIds = null
  ): Collection {
    // Validar que el rango tenga 2 fechas
    if (count($dateRange) !== 2) {
      throw new \InvalidArgumentException('El rango de fechas debe contener exactamente 2 elementos [inicio, fin]');
    }

    [$startDate, $endDate] = $dateRange;

    // Obtener sedes del usuario si no se proporcionaron
    if ($userSedeIds === null) {
      $userSedeIds = $this->getUserSedeIds();
    }

    // Colección para acumular IDs únicos de work orders
    $workOrderIds = collect();

    // PARTE 1: OTs con documentos electrónicos (SIMPLE y MASSIVE)
    $workOrderIdsFromDocuments = $this->getWorkOrderIdsFromElectronicDocuments(
      $startDate,
      $endDate,
      $sedeId,
      $userSedeIds
    );
    $workOrderIds = $workOrderIds->merge($workOrderIdsFromDocuments);

    // PARTE 2: OTs con nota interna SIN factura (INTERNA_SC, INTERNA_CC)
    $workOrderIdsFromInternalNotes = $this->getWorkOrderIdsFromInternalNotes(
      $startDate,
      $endDate,
      $sedeId,
      $userSedeIds
    );
    $workOrderIds = $workOrderIds->merge($workOrderIdsFromInternalNotes);

    // Obtener las OTs únicas
    $uniqueIds = $workOrderIds->unique()->values();

    if ($uniqueIds->isEmpty()) {
      return collect();
    }

    // Retornar las Work Orders completas con relaciones
    return ApWorkOrder::with([
      'invoiceTo.documentType',
      'invoiceTo.typePerson',
      'vehicle.customer.documentType',
      'vehicle.customer.typePerson',
      'vehicle.model.family.brand',
      'vehicle.model.family',
      'sede',
      'advisor',
      'items.typePlanning',
      'plannings.worker',
      'labours',
      'parts.product',
      'typeCurrency',
      'exchangeRate',
      'internalNotes'
    ])
      ->whereIn('id', $uniqueIds)
      ->get();
  }

  /**
   * ESCENARIO 1 y 2: Obtiene IDs de Work Orders desde documentos electrónicos
   * (Facturación SIMPLE y MASIVA)
   *
   * @param string $startDate
   * @param string $endDate
   * @param int|null $sedeId
   * @param array $userSedeIds
   * @return Collection
   */
  private function getWorkOrderIdsFromElectronicDocuments(
    string $startDate,
    string $endDate,
    ?int $sedeId,
    array $userSedeIds
  ): Collection {
    $workOrderIds = collect();

    // Query base para documentos electrónicos finales
    $queryDocuments = ElectronicDocument::query()
      ->with(['workOrder', 'internalNotes.workOrder'])
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false) // Solo comprobantes finales
      ->whereBetween('fecha_de_emision', [$startDate, $endDate])
      ->where(function ($q) {
        // SIMPLE: tiene work_order_id directo
        $q->whereNotNull('work_order_id')
          // MASSIVE: tiene notas internas facturadas
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Filtrar por sedes del usuario autenticado
    if (!empty($userSedeIds)) {
      $queryDocuments->where(function ($q) use ($userSedeIds) {
        // Sede desde workOrder (simple) o desde internalNotes->workOrder (massive)
        $q->whereHas('workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        });
      });
    }

    // Filtro opcional por sede específica
    if ($sedeId !== null) {
      $queryDocuments->where(function ($q) use ($sedeId) {
        $q->whereHas('workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        });
      });
    }

    $documents = $queryDocuments->get();

    // Extraer work_order_ids de los documentos
    foreach ($documents as $document) {
      // ESCENARIO 1: SIMPLE - tiene work_order_id directo
      if ($document->work_order_id) {
        // Validar filtro de sede si existe
        if ($sedeId !== null && $document->workOrder && $document->workOrder->sede_id != $sedeId) {
          // No agregar esta OT
        } elseif (!empty($userSedeIds) && $document->workOrder && !in_array($document->workOrder->sede_id, $userSedeIds)) {
          // No agregar esta OT
        } else {
          $workOrderIds->push($document->work_order_id);
        }
      }

      // ESCENARIO 2: MASSIVE - tiene notas internas
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        foreach ($document->internalNotes as $internalNote) {
          if ($internalNote->work_order_id && $internalNote->workOrder) {
            // Validar filtro de sede si existe
            if ($sedeId !== null && $internalNote->workOrder->sede_id != $sedeId) {
              continue;
            }
            if (!empty($userSedeIds) && !in_array($internalNote->workOrder->sede_id, $userSedeIds)) {
              continue;
            }
            $workOrderIds->push($internalNote->work_order_id);
          }
        }
      }
    }

    return $workOrderIds;
  }

  /**
   * ESCENARIO 3: Obtiene IDs de Work Orders con nota interna SIN factura
   * (INTERNA_SC, INTERNA_CC)
   *
   * @param string $startDate
   * @param string $endDate
   * @param int|null $sedeId
   * @param array $userSedeIds
   * @return Collection
   */
  private function getWorkOrderIdsFromInternalNotes(
    string $startDate,
    string $endDate,
    ?int $sedeId,
    array $userSedeIds
  ): Collection {
    $queryInternalNoteWorkOrders = ApWorkOrder::query()
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->whereHas('internalNotes', function ($q) use ($startDate, $endDate) {
        $q->whereNotNull('number')
          ->whereBetween('created_date', [$startDate, $endDate]);
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
      // NO debe tener documento electrónico asociado
      ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('ap_billing_electronic_documents')
          ->whereColumn('ap_billing_electronic_documents.work_order_id', 'ap_work_orders.id')
          ->where('ap_billing_electronic_documents.anulado', false);
      })
      ->whereDoesntHave('internalNotes', function ($q) {
        $q->whereHas('electronicDocuments');
      });

    // Filtrar por sedes del usuario autenticado
    if (!empty($userSedeIds)) {
      $queryInternalNoteWorkOrders->whereIn('sede_id', $userSedeIds);
    }

    // Filtro opcional por sede específica
    if ($sedeId !== null) {
      $queryInternalNoteWorkOrders->where('sede_id', $sedeId);
    }

    return $queryInternalNoteWorkOrders->pluck('id');
  }

  /**
   * Obtiene solo las OTs con nota interna SIN factura (ESCENARIO 3)
   *
   * @param array $dateRange Array con [fecha_inicio, fecha_fin]
   * @param int|null $sedeId Filtro opcional por sede
   * @param array|null $userSedeIds IDs de sedes del usuario autenticado (opcional)
   * @return Collection Collection de ApWorkOrder
   */
  public function getWorkOrdersWithInternalNoteOnly(
    array $dateRange,
    ?int $sedeId = null,
    ?array $userSedeIds = null
  ): Collection {
    // Validar que el rango tenga 2 fechas
    if (count($dateRange) !== 2) {
      throw new \InvalidArgumentException('El rango de fechas debe contener exactamente 2 elementos [inicio, fin]');
    }

    [$startDate, $endDate] = $dateRange;

    // Obtener sedes del usuario si no se proporcionaron
    if ($userSedeIds === null) {
      $userSedeIds = $this->getUserSedeIds();
    }

    $workOrderIds = $this->getWorkOrderIdsFromInternalNotes(
      $startDate,
      $endDate,
      $sedeId,
      $userSedeIds
    );

    if ($workOrderIds->isEmpty()) {
      return collect();
    }

    return ApWorkOrder::with([
      'invoiceTo.documentType',
      'invoiceTo.typePerson',
      'vehicle.customer.documentType',
      'vehicle.customer.typePerson',
      'vehicle.model.family.brand',
      'vehicle.model.family',
      'sede',
      'advisor',
      'items.typePlanning',
      'plannings.worker',
      'labours',
      'parts.product',
      'typeCurrency',
      'exchangeRate',
      'internalNotes'
    ])
      ->whereIn('id', $workOrderIds)
      ->get();
  }

  /**
   * Obtiene documentos electrónicos de OTs cerradas (para reportes que necesitan info del documento)
   *
   * Este método es útil cuando necesitas no solo las OTs sino también:
   * - El número del documento electrónico
   * - La fecha de emisión del documento
   * - Estado SUNAT
   * - Notas de crédito asociadas
   *
   * @param array $dateRange Array con [fecha_inicio, fecha_fin]
   * @param int|null $sedeId Filtro opcional por sede
   * @param array|null $userSedeIds IDs de sedes del usuario autenticado (opcional)
   * @return Collection Collection de ElectronicDocument con relaciones cargadas
   */
  public function getElectronicDocumentsOfClosedWorkOrders(
    array $dateRange,
    ?int $sedeId = null,
    ?array $userSedeIds = null
  ): Collection {
    // Validar que el rango tenga 2 fechas
    if (count($dateRange) !== 2) {
      throw new \InvalidArgumentException('El rango de fechas debe contener exactamente 2 elementos [inicio, fin]');
    }

    [$startDate, $endDate] = $dateRange;

    // Obtener sedes del usuario si no se proporcionaron
    if ($userSedeIds === null) {
      $userSedeIds = $this->getUserSedeIds();
    }

    // Query de documentos electrónicos
    $queryDocuments = ElectronicDocument::query()
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where('is_advance_payment', false)
      ->whereBetween('fecha_de_emision', [$startDate, $endDate])
      ->where(function ($q) {
        $q->whereNotNull('work_order_id')
          ->orWhereHas('internalNotes', function ($subQ) {
            $subQ->where('status', 'invoiced');
          });
      });

    // Filtrar por sedes del usuario autenticado
    if (!empty($userSedeIds)) {
      $queryDocuments->where(function ($q) use ($userSedeIds) {
        $q->whereHas('workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($userSedeIds) {
          $subQ->whereIn('sede_id', $userSedeIds);
        });
      });
    }

    // Filtro opcional por sede específica
    if ($sedeId !== null) {
      $queryDocuments->where(function ($q) use ($sedeId) {
        $q->whereHas('workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        })->orWhereHas('internalNotes.workOrder', function ($subQ) use ($sedeId) {
          $subQ->where('sede_id', $sedeId);
        });
      });
    }

    return $queryDocuments->get();
  }

  /**
   * Obtiene los IDs de las sedes asociadas al usuario autenticado
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