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
      $updateData = [
        'status_id' => ApMasters::FINISHED_WORK_ORDER_ID,
        'is_invoiced' => false,
        'output_generation_warehouse' => false,
      ];

      // Si es una NC, marcar que tuvo NC (para tracking de re-reserva)
      if ($creditNote && $creditNote->is_nota_credito) {
        $updateData['had_credit_note'] = true;
      }

      $workOrder->update($updateData);

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
   * @param ElectronicDocument|null $relatedDocument Nota de crédito o factura cancelada (null para legacy)
   * @return void
   */
  public function reverseInventoryForWorkOrder(ApWorkOrder $workOrder, ?ElectronicDocument $relatedDocument = null): void
  {
    try {
      // Buscar el movimiento de inventario asociado a la orden de trabajo
      $movement = InventoryMovement::where('reference_type', ApWorkOrder::class)
        ->where('reference_id', $workOrder->id)
        ->where('movement_type', InventoryMovement::TYPE_SALE)
        ->first();

      if ($movement) {
        // ✅ VALIDAR SI YA EXISTE UN RETURN_IN PARA ESTA NC/DOCUMENTO
        if ($relatedDocument) {
          $existingReturn = InventoryMovement::where('reference_type', ElectronicDocument::class)
            ->where('reference_id', $relatedDocument->id)
            ->where('movement_type', InventoryMovement::TYPE_RETURN_IN)
            ->exists();

          if ($existingReturn) {
            Log::info('⚠️ [REVERSE-INVENTORY] Movimiento de devolución ya existe - No crear duplicado', [
              'credit_note_id' => $relatedDocument->id,
              'credit_note_number' => $relatedDocument->full_number,
              'work_order_id' => $workOrder->id,
            ]);
            return; // No crear duplicado
          }
        }

        $inventoryService = app(InventoryMovementService::class);

        // Crear movimiento de devolución (mantiene el movimiento original de SALE)
        $inventoryService->createReturnMovementForWorkOrder(
          $relatedDocument,
          $workOrder,
          null // null = devolución total de todos los productos
        );
      }
    } catch (Exception $e) {
      Log::error('Error al crear movimiento de devolución para orden de trabajo', [
        'work_order_id' => $workOrder->id,
        'related_document_id' => $relatedDocument->id ?? null,
        'error' => $e->getMessage(),
      ]);
    }
  }
}