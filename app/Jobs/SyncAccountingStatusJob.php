<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\taller\ApOrderQuotationsService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * php artisan queue:work --tries=3
 */
class SyncAccountingStatusJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  public function __construct()
  {
    $this->onQueue('electronic_documents');
  }

  public function handle(): void
  {
    $documents = ElectronicDocument::where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED)
      ->where(function ($query) {
        $query->where('is_accounted', false)
          ->orWhereNull('is_accounted');
      })
      ->get();

    foreach ($documents as $document) {
      try {
        $sopRecord = DB::connection('dbtest')
          ->table('SOP30200')
          ->where('SOPNUMBE', 'like', '%' . $document->full_number . '%')
          ->first();

        if ($sopRecord) {
          $isAnnulled = $sopRecord->VOIDSTTS == "1";

          if (!$isAnnulled) {
            $rmRecord = DB::connection('dbtest')
              ->table('RM20101')
              ->where('DOCNUMBR', 'like', '%' . $document->full_number . '%')
              ->whereNot('RMDTYPAL', '9')
              ->first();

            if ($rmRecord) {
              $isAnnulled = $rmRecord->VOIDSTTS == "1";
            }
          }

          $wasAccounted = $document->is_accounted;

          $document->update([
            'is_accounted' => true,
            'is_annulled' => $isAnnulled,
          ]);

          if (!$wasAccounted && !$isAnnulled) {
            if ($document->area_id === ApMasters::AREA_COMERCIAL) {
              $this->restoreVehicleToInventoryIfApplicable($document);
            } else {
              $this->createInventoryMovementIfApplicable($document);
            }
          }

          // Reversión de estados e inventario para NC contabilizadas (primera vez o re-procesamiento)
          if (!$isAnnulled && $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
            if ($document->area_id === ApMasters::AREA_COMERCIAL) {
              // Comercial ya tiene su lógica (no tocar)
              $this->restoreVehicleToInventoryIfApplicable($document);
            } else {
              // Postventa - Nueva lógica de reversión
              $this->reversePostventaStatusIfApplicable($document);
            }
          }
        } else {
          $document->update([
            'is_accounted' => false,
            'is_annulled' => false,
          ]);
        }
      } catch (Throwable $e) {
        Log::error('Error al sincronizar estado contable desde Dynamics', [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'error' => $e->getMessage(),
        ]);
      }
    }
  }

  private function restoreVehicleToInventoryIfApplicable(ElectronicDocument $document): void
  {
    if ($document->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_CREDITO) {
      return;
    }

    $restorableTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    if (!in_array($document->sunat_concept_credit_note_type_id, $restorableTypes)) {
      return;
    }

    $originalDocument = $document->originalDocument;

    if (!$originalDocument || !$originalDocument->ap_vehicle_movement_id) {
      return;
    }

    $vehicle = $originalDocument->vehicle;

    if (!$vehicle) {
      return;
    }

    $vehicle->update(['ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN]);
  }

  /**
   * Crear movimiento de inventario para cotizaciones u órdenes de trabajo
   * solo después de que la última factura haya sido contabilizada en Dynamics
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function createInventoryMovementIfApplicable(ElectronicDocument $document): void
  {
    // Procesar cotizaciones
    if ($document->order_quotation_id) {
      $this->createInventoryMovementForQuotation($document->order_quotation_id);
    }

    // Procesar órdenes de trabajo
    if ($document->work_order_id) {
      $this->createInventoryMovementForWorkOrder($document->work_order_id);
    }
  }

  /**
   * Crear movimiento de inventario para cotización totalmente pagada
   *
   * Ahora primero evalúa y actualiza el estado de pago usando el método centralizado
   * en ApOrderQuotationsService antes de crear el movimiento de inventario.
   *
   * @param int $quotationId
   * @return void
   */
  private function createInventoryMovementForQuotation(int $quotationId): void
  {
    try {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        return;
      }

      // Evaluar y actualizar el estado de pago de la cotización
      // Este método centralizado actualiza is_fully_paid y status si cumple las condiciones
      $quotationService = app(ApOrderQuotationsService::class);
      $quotationService->evaluateAndUpdateQuotationPaymentStatus($quotationId);

      // Refrescar el modelo para obtener los valores actualizados
      $quotation->refresh();

      // Verificar que la cotización esté totalmente pagada Y no tenga salida de inventario generada
      if (!$quotation->is_fully_paid || $quotation->output_generation_warehouse) {
        return;
      }

      // Crear la salida de inventario
      $inventoryMovementService = app(InventoryMovementService::class);
      $inventoryMovementService->createSaleFromQuotation($quotationId);
    } catch (Exception $e) {
      Log::error('Error al crear movimiento de inventario para cotización', [
        'quotation_id' => $quotationId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Crear movimiento de inventario para orden de trabajo totalmente facturada
   *
   * Este método se ejecuta cuando una factura final (is_advance_payment = 0)
   * de una OT es contabilizada en Dynamics (is_accounted = true).
   *
   * @param int $workOrderId
   * @return void
   */
  private function createInventoryMovementForWorkOrder(int $workOrderId): void
  {
    try {
      $workOrder = ApWorkOrder::with('advancesWorkOrder')->find($workOrderId);

      if (!$workOrder) {
        return;
      }

      // Si ya generó salida de inventario, no hacer nada
      if ($workOrder->output_generation_warehouse) {
        return;
      }

      // Verificar si existe una factura final (is_advance_payment = 0) contabilizada
      $finalInvoice = $workOrder->getFinalInvoice();

      if (!$finalInvoice) {
        return; // No hay factura final aún
      }

      // Verificar que la factura final esté contabilizada en Dynamics
      if (!$finalInvoice->is_accounted) {
        return; // La factura final aún no está contabilizada
      }

      // Crear la salida de inventario
      $inventoryMovementService = app(InventoryMovementService::class);
      $movement = $inventoryMovementService->createSaleFromWorkOrder($workOrderId);

      // Actualizar electronic_document_id con la factura final
      $movement->update(['electronic_document_id' => $finalInvoice->id]);

      // Marcar la OT como facturada y cerrada
      $workOrder->update([
        'is_invoiced' => true,
        'status_id' => ApMasters::CLOSED_WORK_ORDER_ID,
        'output_generation_warehouse' => true,
      ]);

    } catch (Exception $e) {
      Log::error('Error al crear movimiento de inventario para orden de trabajo', [
        'work_order_id' => $workOrderId,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Revertir estados e inventario de cotizaciones/OT cuando una NC de postventa es contabilizada
   * Solo aplica para NC de facturas finales (is_advance_payment = 0)
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function reversePostventaStatusIfApplicable(ElectronicDocument $document): void
  {
    // Solo procesar áreas de postventa
    if (!in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
      return;
    }

    // Solo procesar Notas de Crédito
    if ($document->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_CREDITO) {
      return;
    }

    // Obtener documento original
    $originalDocument = $document->originalDocument;
    if (!$originalDocument) {
      return;
    }

    // Solo revertir si el documento original es FACTURA FINAL (no anticipo)
    if ($originalDocument->is_advance_payment) {
      return; // Los anticipos no generan movimiento de inventario
    }

    // Delegar según tipo de NC
    $creditNoteType = $document->sunat_concept_credit_note_type_id;

    switch ($creditNoteType) {
      case SunatConcepts::ID_CREDIT_NOTE_ANULACION:
        $this->reverseForAnulacion($originalDocument, $document);
        break;

      case SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL:
        $this->reverseForDevolucionTotal($originalDocument, $document);
        break;

      case SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_ITEM:
        $this->reverseForDevolucionParcial($document, $originalDocument);
        break;

      default:
        // Otros tipos de NC (descuentos, bonificaciones, etc.) no requieren reversión de estados/inventario
        Log::info('NC contabilizada sin reversión de estados', [
          'credit_note_id' => $document->id,
          'credit_note_type_id' => $creditNoteType,
          'original_document_id' => $originalDocument->id,
        ]);
        break;
    }
  }

  /**
   * Reversión para NC por Anulación (código 01)
   * Revierte TODO: estados + inventario
   *
   * @param ElectronicDocument $originalDocument
   * @param ElectronicDocument $creditNote
   * @return void
   */
  private function reverseForAnulacion(ElectronicDocument $originalDocument, ElectronicDocument $creditNote): void
  {
    // Revertir cotización si existe
    if ($originalDocument->order_quotation_id) {
      $this->reverseQuotationStatus($originalDocument->order_quotation_id);
    }

    // Revertir orden de trabajo si existe
    if ($originalDocument->work_order_id) {
      $this->reverseWorkOrderStatus($originalDocument->work_order_id, $creditNote);
    }
  }

  /**
   * Reversión para NC por Devolución Total (código 06)
   * Revierte TODO: estados + inventario
   *
   * @param ElectronicDocument $originalDocument
   * @param ElectronicDocument $creditNote
   * @return void
   */
  private function reverseForDevolucionTotal(ElectronicDocument $originalDocument, ElectronicDocument $creditNote): void
  {
    // Misma lógica que anulación
    $this->reverseForAnulacion($originalDocument, $creditNote);
  }

  /**
   * Revertir estados e inventario de una cotización
   *
   * @param int $quotationId
   * @return void
   */
  private function reverseQuotationStatus(int $quotationId): void
  {
    try {
      $quotation = ApOrderQuotations::find($quotationId);

      if (!$quotation) {
        return;
      }

      // Revertir inventario si existe
      if ($quotation->output_generation_warehouse) {
        $this->reverseInventoryForQuotation($quotation);
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
   * Revertir estados e inventario de una orden de trabajo
   *
   * @param int $workOrderId
   * @param ElectronicDocument $creditNote
   * @return void
   */
  private function reverseWorkOrderStatus(int $workOrderId, ElectronicDocument $creditNote): void
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
   * Revertir movimiento de inventario de una cotización
   *
   * @param ApOrderQuotations $quotation
   * @return void
   */
  private function reverseInventoryForQuotation(ApOrderQuotations $quotation): void
  {
    try {
      // Buscar el movimiento de inventario asociado a la cotización
      $movement = InventoryMovement::where('reference_type', ApOrderQuotations::class)
        ->where('reference_id', $quotation->id)
        ->where('movement_type', InventoryMovement::TYPE_SALE)
        ->first();

      if ($movement) {
        $inventoryService = app(InventoryMovementService::class);
        $inventoryService->reverseStockFromMovement($movement->id);
      }
    } catch (Exception $e) {
      Log::error('Error al revertir inventario de cotización', [
        'quotation_id' => $quotation->id,
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
   * @param ElectronicDocument $creditNote
   * @return void
   */
  private function reverseInventoryForWorkOrder(ApWorkOrder $workOrder, ElectronicDocument $creditNote): void
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

  /**
   * Reversión parcial para NC por Devolución de Ítem (código 02)
   * Revierte solo los ítems específicos de la NC en la OT
   * Actualiza ApWorkOrderParts y devuelve stock al almacén
   *
   * @param ElectronicDocument $creditNote
   * @param ElectronicDocument $originalDocument
   * @return void
   */
  private function reverseForDevolucionParcial(ElectronicDocument $creditNote, ElectronicDocument $originalDocument): void
  {
    // Solo para órdenes de trabajo (cotizaciones tienen lógica diferente)
    if (!$originalDocument->work_order_id) {
      return;
    }

    try {
      $workOrder = ApWorkOrder::find($originalDocument->work_order_id);

      if (!$workOrder) {
        return;
      }

      // Obtener los ítems de la NC para saber qué repuestos devolver
      $creditNoteItems = $creditNote->items; // ElectronicDocumentItem
      $itemsToReturn = [];

      foreach ($creditNoteItems as $item) {
        // Buscar el repuesto correspondiente en la OT
        $workOrderPart = ApWorkOrderParts::where('work_order_id', $workOrder->id)
          ->where('product_id', $item->product_id)
          ->first();

        if (!$workOrderPart) {
          Log::warning('Repuesto no encontrado en OT para NC parcial', [
            'credit_note_id' => $creditNote->id,
            'work_order_id' => $workOrder->id,
            'product_id' => $item->product_id,
          ]);
          continue;
        }

        // Cantidad a devolver
        $quantityToReturn = $item->quantity;

        // Guardar para el movimiento de inventario
        $itemsToReturn[] = [
          'product_id' => $item->product_id,
          'quantity' => $quantityToReturn,
        ];

        // Actualizar la cantidad en ApWorkOrderParts
        $newQuantity = $workOrderPart->quantity_used - $quantityToReturn;

        if ($newQuantity <= 0) {
          // Si la devolución es total de este ítem, eliminarlo
          $workOrderPart->delete();
        } else {
          // Actualizar cantidad y recalcular montos
          $workOrderPart->quantity_used = $newQuantity;
          $workOrderPart->total_cost = $workOrderPart->unit_price * $newQuantity;

          if ($workOrderPart->discount_percentage > 0) {
            $discountAmount = $workOrderPart->total_cost * ($workOrderPart->discount_percentage / 100);
            $workOrderPart->net_amount = $workOrderPart->total_cost - $discountAmount;
          } else {
            $workOrderPart->net_amount = $workOrderPart->total_cost;
          }

          $workOrderPart->save();
        }
      }

      // Recalcular totales de la OT
      $workOrder->calculateTotals();

      // Crear movimiento de inventario de devolución parcial
      if (!empty($itemsToReturn)) {
        $inventoryService = app(InventoryMovementService::class);
        $returnMovement = $inventoryService->createReturnMovementFromCreditNote(
          $creditNote,
          $workOrder,
          $itemsToReturn // Array de ítems a devolver
        );
      }
    } catch (Exception $e) {
      Log::error('Error al procesar NC por ítem', [
        'credit_note_id' => $creditNote->id,
        'original_document_id' => $originalDocument->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  public function failed(Throwable $exception): void
  {
    Log::error('SyncAccountingStatusJob falló', ['error' => $exception->getMessage()]);
  }
}
