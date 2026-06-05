<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para revertir estados e inventario de órdenes de trabajo
 *
 * Este servicio contiene la lógica compartida para:
 * - Revertir estados de orden de trabajo (status, is_invoiced, output_generation_warehouse)
 * - Revertir inventario creando movimientos RETURN_IN
 *
 * Usado por:
 * - SyncAccountingStatusJob (cuando se contabiliza una NC en Dynamics)
 * - ElectronicDocumentService::cancelInNubefact (cuando se cancela factura directamente)
 */
class ApWorkOrderReversalService
{
  /**
   * Revertir estados e inventario de una orden de trabajo
   *
   * @param int $workOrderId
   * @param ElectronicDocument|null $creditNote Nota de crédito que origina la reversión (null si es cancelación de factura)
   * @return void
   */
  public function reverseWorkOrderStatus(int $workOrderId, ?ElectronicDocument $creditNote = null): void
  {
    try {
      $workOrder = ApWorkOrder::find($workOrderId);

      if (!$workOrder) {
        return;
      }

      // Revertir inventario si existe
      if ($workOrder->output_generation_warehouse) {
        $this->reverseInventoryForWorkOrder($workOrder, $creditNote);
      }

      // Revertir estados
      $workOrder->update([
        'status_id' => ApMasters::FINISHED_WORK_ORDER_ID,
        'is_invoiced' => false,
        'output_generation_warehouse' => false,
      ]);

    } catch (Exception $e) {
      Log::error('Error al revertir estado de orden de trabajo', [
        'work_order_id' => $workOrderId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir movimiento de inventario de una orden de trabajo
   * Solo repuestos (ApWorkOrderParts), no mano de obra
   *
   * Crea un movimiento de devolución (RETURN_IN) sin eliminar el movimiento original de venta
   *
   * @param ApWorkOrder $workOrder
   * @param ElectronicDocument|null $creditNote Nota de crédito (null si es cancelación de factura)
   * @return void
   */
  public function reverseInventoryForWorkOrder(ApWorkOrder $workOrder, ?ElectronicDocument $creditNote = null): void
  {
    try {
      // Buscar el movimiento de inventario asociado a la orden de trabajo
      $movement = InventoryMovement::where('reference_type', ApWorkOrder::class)
        ->where('reference_id', $workOrder->id)
        ->where('movement_type', InventoryMovement::TYPE_SALE)
        ->first();

      if ($movement) {
        $inventoryService = app(InventoryMovementService::class);

        // Crear movimiento de devolución por NC (mantiene el movimiento original de SALE)
        $inventoryService->createReturnMovementFromCreditNote(
          $creditNote,
          $workOrder,
          null // null = devolución total de todos los productos
        );
      }
    } catch (Exception $e) {
      Log::error('Error al crear movimiento de devolución para orden de trabajo', [
        'work_order_id' => $workOrder->id,
        'credit_note_id' => $creditNote->id ?? null,
        'error' => $e->getMessage(),
      ]);
    }
  }
}