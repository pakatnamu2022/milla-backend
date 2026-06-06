<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para revertir estados e inventario de cotizaciones
 *
 * Este servicio contiene la lógica compartida para:
 * - Revertir estados de cotización (status, is_fully_paid, output_generation_warehouse)
 * - Revertir inventario creando movimientos RETURN_IN
 *
 * Usado por:
 * - SyncAccountingStatusJob (cuando se contabiliza una NC en Dynamics)
 * - ElectronicDocumentService::cancelInNubefact (cuando se cancela factura directamente)
 */
class ApOrderQuotationsReversalService
{
  /**
   * Revertir estados e inventario de una cotización
   *
   * @param int $quotationId
   * @param ElectronicDocument|null $creditNote Nota de crédito que origina la reversión (null si es cancelación de factura)
   * @return void
   */
  public function reverseQuotationStatus(int $quotationId, ?ElectronicDocument $creditNote = null): void
  {
    try {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        return;
      }

      // Revertir inventario si existe
      if ($quotation->output_generation_warehouse) {
        $this->reverseInventoryForQuotation($quotation, $creditNote);
      }

      // Revertir estados
      $quotation->update([
        'status' => ApOrderQuotations::STATUS_POR_FACTURAR,
        'is_fully_paid' => false,
        'output_generation_warehouse' => false,
      ]);

    } catch (Exception $e) {
      Log::error('Error al revertir estado de cotización', [
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir movimiento de inventario de una cotización
   * Solo productos (PRODUCT), no mano de obra (LABOR)
   *
   * Crea un movimiento de devolución (RETURN_IN) sin eliminar el movimiento original de venta
   *
   * @param ApOrderQuotations $quotation
   * @param ElectronicDocument|null $relatedDocument Nota de crédito o factura cancelada (null para legacy)
   * @return void
   */
  public function reverseInventoryForQuotation(ApOrderQuotations $quotation, ?ElectronicDocument $relatedDocument = null): void
  {
    try {
      // Buscar el movimiento de inventario asociado a la cotización
      $movement = InventoryMovement::where('reference_type', ApOrderQuotations::class)
        ->where('reference_id', $quotation->id)
        ->where('movement_type', InventoryMovement::TYPE_SALE)
        ->first();

      if ($movement) {
        $inventoryService = app(InventoryMovementService::class);

        // Crear movimiento de devolución (mantiene el movimiento original de SALE)
        $inventoryService->createReturnMovementForQuotation(
          $relatedDocument,
          $quotation,
          null // null = devolución total de todos los productos
        );
      }
    } catch (Exception $e) {
      Log::error('Error al crear movimiento de devolución para cotización', [
        'quotation_id' => $quotation->id,
        'related_document_id' => $relatedDocument->id ?? null,
        'error' => $e->getMessage(),
      ]);
    }
  }
}