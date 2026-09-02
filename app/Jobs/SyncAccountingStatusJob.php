<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Http\Services\ap\postventa\gestionProductos\StockReReservationService;
use App\Http\Services\ap\postventa\taller\ApOrderQuotationsReversalService;
use App\Http\Services\ap\postventa\taller\ApWorkOrderReversalService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**ds
 * php artisan queue:work --tries=3
 */
class SyncAccountingStatusJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  public function __construct(public readonly ?int $documentId = null)
  {
    $this->onQueue('electronic_documents');
  }

  public function handle(): void
  {
    $query = ElectronicDocument::where('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED);

    if ($this->documentId) {
      // Cuando se consulta por ID específico, permitir cualquier área
      $documents = $query->where('id', $this->documentId)->get();
    } else {
      // IMPORTANTE: Excluir áreas 881 (Taller) y 882 (Mesón/Repuestos) del procesamiento masivo
      // Estas áreas deben consultarse individualmente por ID para validar stock correctamente
      // antes de generar salidas de inventario
      $documents = $query
        ->where(function ($q) {
          $q->where('is_accounted', false)->orWhereNull('is_accounted');
        })
        ->whereNotIn('area_id', [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])
        ->get();
    }

    foreach ($documents as $document) {
      try {
        // ===== TEST PREVENTIVO ANTES DE CONSULTAR DYNAMICS =====
        Log::info('🔍 [SYNC-ACCOUNTING] TEST PREVENTIVO - Iniciando validación', [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'area_id' => $document->area_id,
          'is_nota_credito' => $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO,
          'work_order_id' => $document->work_order_id,
          'is_accounted_before' => $document->is_accounted,
        ]);

        // Si es nota de crédito de postventa, loguear stock ANTES
        if ($document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO &&
          in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
          $this->logStockBeforeProcessing($document);
        }

        $sopRecord = DB::connection(Company::CONNECTION_DYNAMICS_3)
          ->table('SOP30200')
          ->where('SOPNUMBE', $document->full_number)
          ->first();

        Log::info('🔍 [SYNC-ACCOUNTING] Consulta a Dynamics SOP30200', [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'found_in_sop' => $sopRecord ? 'SI' : 'NO',
          'voidstts' => $sopRecord->VOIDSTTS ?? null,
        ]);

        if ($sopRecord) {
          $isAnnulled = $sopRecord->VOIDSTTS == "1";

          if (!$isAnnulled) {
            $rmRecord = DB::connection(Company::CONNECTION_DYNAMICS_3)
              ->table('RM20101')
              ->where('DOCNUMBR', $document->full_number)
              ->whereNot('RMDTYPAL', '9')
              ->first();

            if ($rmRecord) {
              $isAnnulled = $rmRecord->VOIDSTTS == "1";
              Log::info('🔍 [SYNC-ACCOUNTING] Consulta a Dynamics RM20101', [
                'document_id' => $document->id,
                'full_number' => $document->full_number,
                'found_in_rm' => 'SI',
                'voidstts' => $rmRecord->VOIDSTTS ?? null,
              ]);
            }
          }

          $wasAccounted = $document->is_accounted;

          Log::info('📝 [SYNC-ACCOUNTING] Actualizando estado del documento', [
            'document_id' => $document->id,
            'full_number' => $document->full_number,
            'was_accounted_before' => $wasAccounted,
            'is_accounted_now' => true,
            'is_annulled' => $isAnnulled,
          ]);

          $document->update([
            'is_accounted' => true,
            'is_annulled' => $isAnnulled,
          ]);

          if (!$wasAccounted && !$isAnnulled) {
            if ($document->area_id === ApMasters::AREA_COMERCIAL) {
              $this->confirmVehicleMovement($document);
              $this->restoreVehicleToInventoryIfApplicable($document);
            } else {
              $this->createInventoryMovementIfApplicable($document);
            }
          }

          // Reversión de estados e inventario para NC contabilizadas (primera vez o re-procesamiento)
          if (!$isAnnulled && $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
            Log::info('🔄 [SYNC-ACCOUNTING-NC] DETECTADA NOTA DE CRÉDITO CONTABILIZADA', [
              'document_id' => $document->id,
              'full_number' => $document->full_number,
              'area_id' => $document->area_id,
              'credit_note_type_id' => $document->sunat_concept_credit_note_type_id,
              'work_order_id' => $document->work_order_id,
              'original_document_id' => $document->original_document_id,
              'was_accounted_before' => $wasAccounted,
            ]);

            // ⚠️ SIMULACIÓN: Loguear QUÉ VA A HACER antes de ejecutar
            if (in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
              $this->logSimulationBeforeProcessing($document);
            }

            // ⚠️ DD TEMPORAL - COMENTADO PARA EJECUTAR
            // if (in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
            //   dd([
            //     'mensaje' => '🛑 PROCESO DETENIDO - Revisa storage/logs/laravel.log',
            //     'document_id' => $document->id,
            //     'full_number' => $document->full_number,
            //     'area' => $document->area_id === ApMasters::AREA_TALLER ? 'TALLER' : 'MESON',
            //     'credit_note_type' => $document->sunat_concept_credit_note_type_id,
            //     'work_order_id' => $document->work_order_id,
            //     'original_document_id' => $document->original_document_id,
            //     'instrucciones' => [
            //       '1. Busca en laravel.log: [STOCK-BEFORE] = Estado actual',
            //       '2. Busca en laravel.log: [SIMULATION] = Qué va a hacer',
            //       '3. Compara los valores para identificar el problema',
            //       '4. Cuando lo arregles, comenta o elimina este dd()',
            //     ]
            //   ]);
            // }

            if ($document->area_id === ApMasters::AREA_COMERCIAL) {
              // Comercial ya tiene su lógica (no tocar)
              $this->restoreVehicleToInventoryIfApplicable($document);
            } else {
              // Postventa - Nueva lógica de reversión
              $this->reversePostventaStatusIfApplicable($document);
            }

            // Si es nota de crédito de postventa, loguear stock DESPUÉS
            if (in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
              $this->logStockAfterProcessing($document);
            }
          }
        } else {
          Log::info('⚠️ [SYNC-ACCOUNTING] Documento NO encontrado en Dynamics', [
            'document_id' => $document->id,
            'full_number' => $document->full_number,
          ]);

          $document->update([
            'is_accounted' => false,
            'is_annulled' => false,
          ]);
        }
      } catch (Throwable $e) {
        Log::error('❌ [SYNC-ACCOUNTING] Error al sincronizar estado contable desde Dynamics', [
          'document_id' => $document->id,
          'full_number' => $document->full_number,
          'error' => $e->getMessage(),
          'trace' => $e->getTraceAsString(),
        ]);
      }
    }
  }

  private function confirmVehicleMovement(ElectronicDocument $document): void
  {
    if (!$document->ap_vehicle_movement_id) {
      return;
    }

    \App\Models\ap\comercial\VehicleMovement::where('id', $document->ap_vehicle_movement_id)
      ->whereNull('confirmed_at')
      ->update(['confirmed_at' => now()]);
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

    // Procesar masivas - buscar work_order_id en internal_notes (pueden ser varias OTs)
    if ($document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $internalNotes = $document->internalNotes()->get();
      foreach ($internalNotes as $internalNote) {
        if ($internalNote->work_order_id) {
          $this->createInventoryMovementForWorkOrder($internalNote->work_order_id, $document);
        }
      }
    }
  }

  /**
   * Crear movimiento de inventario para cotización totalmente facturada
   *
   * Este método se ejecuta cuando una factura final (is_advance_payment = 0)
   * de una cotización es contabilizada en Dynamics (is_accounted = true).
   *
   * @param int $quotationId
   * @return void
   */
  private function createInventoryMovementForQuotation(int $quotationId): void
  {
    try {
      $quotation = ApOrderQuotations::with(['details'])->find($quotationId);

      if (!$quotation) {
        return;
      }

      // Si ya generó salida de inventario, no hacer nada
      if ($quotation->output_generation_warehouse) {
        return;
      }

      // Verificar si existe una factura final (is_advance_payment = 0) contabilizada
      $finalInvoice = $quotation->getFinalInvoice();

      if (!$finalInvoice) {
        return; // No hay factura final aún
      }

      // Verificar que la factura final esté contabilizada en Dynamics
      if (!$finalInvoice->is_accounted) {
        return; // La factura final aún no está contabilizada
      }

      // Verificar si la cotización tiene productos (repuestos) que NO sean travesía
      $hasProducts = $quotation->details
        ->where('product_id', '!=', null)
        ->where('is_traverse', false)
        ->isNotEmpty();

      // Si tiene productos, crear la salida de inventario
      if ($hasProducts) {
        $inventoryMovementService = app(InventoryMovementService::class);
        $movement = $inventoryMovementService->createSaleFromQuotation($quotationId);

        // Actualizar electronic_document_id con la factura final
        $movement->update(['electronic_document_id' => $finalInvoice->id]);
      }

      // Marcar la cotización como totalmente pagada y facturada (con o sin repuestos)
      $quotation->update([
        'is_fully_paid' => true,
        'status_id' => ApMasters::STATUS_ORDER_QUOTE_FACTURADO,
        'output_generation_warehouse' => true,
      ]);

      // Si Cotización es virtual marcar delivery_document_number , customer_signature_delivery_url
      if ($quotation->confirmation_channel === ApOrderQuotations::CONFIRMATION_CHANNEL_VIRTUAL) {
        $quotation->update([
          'delivery_document_number' => $quotation->client->num_doc,
        ]);
      }
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
   * @param ElectronicDocument|null $invoice Para masivas, se pasa el documento directamente
   * @return void
   */
  private function createInventoryMovementForWorkOrder(int $workOrderId, ?ElectronicDocument $invoice = null): void
  {
    try {
      $workOrder = ApWorkOrder::with(['advancesWorkOrder', 'parts'])->find($workOrderId);

      if (!$workOrder) {
        return;
      }

      // Si ya generó salida de inventario, no hacer nada
      if ($workOrder->output_generation_warehouse) {
        return;
      }

      // Determinar la factura a usar
      if ($invoice) {
        // Es masiva - usar el documento pasado directamente (sin validaciones adicionales)
        $finalInvoice = $invoice;
      } else {
        // Es simple - validar que exista factura final contabilizada
        $finalInvoice = $workOrder->getFinalInvoice();

        if (!$finalInvoice) {
          return; // No hay factura final aún
        }

        // Verificar que la factura final esté contabilizada en Dynamics
        if (!$finalInvoice->is_accounted) {
          return; // La factura final aún no está contabilizada
        }
      }

      // Verificar si la orden tiene repuestos (productos) que NO sean travesía
      $hasProducts = $workOrder->parts
        ->where('product_id', '!=', null)
        ->where('is_traverse', false)
        ->isNotEmpty();

      // Si tiene repuestos, crear la salida de inventario
      if ($hasProducts) {
        $inventoryMovementService = app(InventoryMovementService::class);
        $movement = $inventoryMovementService->createSaleFromWorkOrder($workOrderId);

        // Actualizar electronic_document_id con la factura final
        $movement->update(['electronic_document_id' => $finalInvoice->id]);
      }

      // Marcar la OT como facturada y cerrada (con o sin repuestos)
      $workOrder->update([
        'is_invoiced' => true,
        'status_id' => ApMasters::CLOSED_WORK_ORDER_ID,
        'output_generation_warehouse' => true,
        'official_closing_date' => $finalInvoice->fecha_de_emision,
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
      $this->reverseQuotationStatus($originalDocument->order_quotation_id, $creditNote);
    }

    // Revertir orden de trabajo si existe
    if ($originalDocument->work_order_id) {
      $this->reverseWorkOrderStatus($originalDocument->work_order_id, $creditNote);
    }

    // Revertir masivas - buscar work_order_id en internal_notes (pueden ser varias OTs)
    if ($originalDocument->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $internalNotes = $originalDocument->internalNotes()->get();
      foreach ($internalNotes as $internalNote) {
        if (!$internalNote->work_order_id) {
          continue;
        }

        $workOrder = ApWorkOrder::find($internalNote->work_order_id);
        if (!$workOrder) {
          continue;
        }

        // Para comprobantes masivos con NC:
        // 1. Revertir SOLO el inventario (sin cambiar estados de la OT)
        if ($workOrder->output_generation_warehouse) {
          $reversalService = app(ApWorkOrderReversalService::class);
          $reversalService->reverseInventoryForWorkOrder($workOrder, $creditNote);
        }

        // 2. Cambiar el status de la internal_note de 'invoiced' a 'pending'
        if ($internalNote->status === ApInternalNote::STATUS_INVOICED) {
          $internalNote->update([
            'status' => ApInternalNote::STATUS_PENDING,
            'closed_date' => null,
          ]);
        }
      }
    }

    // RE-RESERVA AUTOMÁTICA: Si re_invoice = true, re-reservar stock para refacturación
    $this->handleStockReReservationIfApplicable($creditNote, $originalDocument);
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
   * @param ElectronicDocument $creditNote
   * @return void
   */
  private function reverseQuotationStatus(int $quotationId, ElectronicDocument $creditNote): void
  {
    // Delegar al servicio centralizado
    $reversalService = app(ApOrderQuotationsReversalService::class);
    $reversalService->reverseQuotationStatus($quotationId, $creditNote);
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
    // Delegar al servicio centralizado
    $reversalService = app(ApWorkOrderReversalService::class);
    $reversalService->reverseWorkOrderStatus($workOrderId, $creditNote);
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
    // Obtener IDs de órdenes de trabajo (pueden ser varias en masivas)
    $workOrderIds = [];

    if ($originalDocument->work_order_id) {
      $workOrderIds[] = $originalDocument->work_order_id;
    }

    if ($originalDocument->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $internalNotes = $originalDocument->internalNotes()->get();
      foreach ($internalNotes as $internalNote) {
        if ($internalNote->work_order_id && !in_array($internalNote->work_order_id, $workOrderIds)) {
          $workOrderIds[] = $internalNote->work_order_id;
        }
      }
    }

    // Solo para órdenes de trabajo (cotizaciones tienen lógica diferente)
    if (empty($workOrderIds)) {
      return;
    }

    try {
      // Obtener los ítems de la NC para saber qué repuestos devolver
      $creditNoteItems = $creditNote->items; // ElectronicDocumentItem

      // Procesar cada OT
      foreach ($workOrderIds as $workOrderId) {
        $workOrder = ApWorkOrder::find($workOrderId);

        if (!$workOrder) {
          continue;
        }

        $itemsToReturn = [];

        foreach ($creditNoteItems as $item) {
          // Buscar el repuesto correspondiente en esta OT específica
          $workOrderPart = ApWorkOrderParts::where('work_order_id', $workOrder->id)
            ->where('product_id', $item->product_id)
            ->first();

          if (!$workOrderPart) {
            // El producto no está en esta OT, continuar buscando en otras
            continue;
          }

          // Si el repuesto es travesía, no debe devolverse porque nunca salió del inventario
          if ($workOrderPart->is_traverse) {
            Log::info('Repuesto de travesía ignorado en NC parcial (no afecta inventario)', [
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

        // Crear movimiento de inventario de devolución parcial para esta OT
        if (!empty($itemsToReturn)) {
          $inventoryService = app(InventoryMovementService::class);
          $returnMovement = $inventoryService->createReturnMovementForWorkOrder(
            $creditNote,
            $workOrder,
            $itemsToReturn // Array de ítems a devolver
          );
        }
      }
    } catch (Exception $e) {
      Log::error('Error al procesar NC por ítem', [
        'credit_note_id' => $creditNote->id,
        'original_document_id' => $originalDocument->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Maneja la re-reserva automática de stock cuando re_invoice = true
   *
   * Cuando una NC tiene re_invoice = true, significa que se va a refacturar.
   * Por lo tanto, el stock que regresó al almacén debe volver a RESERVADO
   * para que cuando se contabilice el nuevo comprobante, NO afecte el reservado.
   *
   * @param ElectronicDocument $creditNote
   * @param ElectronicDocument $originalDocument
   * @return void
   */
  private function handleStockReReservationIfApplicable(ElectronicDocument $creditNote, ElectronicDocument $originalDocument): void
  {
    // Solo procesar si re_invoice = true
    if (!$creditNote->re_invoice) {
      return;
    }

    try {
      $reReservationService = app(StockReReservationService::class);

      // Re-reservar para cotización si existe
      if ($originalDocument->order_quotation_id) {
        try {
          $reReservationService->reReserveStockForQuotation($originalDocument->order_quotation_id);
        } catch (Exception $e) {
          Log::error('Error en re-reserva automática para cotización', [
            'credit_note_id' => $creditNote->id,
            'quotation_id' => $originalDocument->order_quotation_id,
            'error' => $e->getMessage(),
          ]);
        }
      }

      // Re-reservar para orden de trabajo si existe
      if ($originalDocument->work_order_id) {
        try {
          $reReservationService->reReserveStockForWorkOrder($originalDocument->work_order_id);

        } catch (Exception $e) {
          Log::error('Error en re-reserva automática para OT', [
            'credit_note_id' => $creditNote->id,
            'work_order_id' => $originalDocument->work_order_id,
            'error' => $e->getMessage(),
          ]);
        }
      }

      // Re-reservar para masivas - buscar work_order_id en internal_notes (pueden ser varias OTs)
      if ($originalDocument->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
        $internalNotes = $originalDocument->internalNotes()->get();

        foreach ($internalNotes as $internalNote) {
          if (!$internalNote->work_order_id) {
            continue;
          }

          try {
            $reReservationService->reReserveStockForWorkOrder($internalNote->work_order_id);
          } catch (Exception $e) {
            Log::error('Error en re-reserva automática para OT masiva', [
              'credit_note_id' => $creditNote->id,
              'work_order_id' => $internalNote->work_order_id,
              'internal_note_id' => $internalNote->id,
              'error' => $e->getMessage(),
            ]);
          }
        }
      }

    } catch (Exception $e) {
      Log::error('Error general en re-reserva automática', [
        'credit_note_id' => $creditNote->id,
        'original_document_id' => $originalDocument->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Loguear SIMULACIÓN de lo que va a hacer (sin ejecutar)
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function logSimulationBeforeProcessing(ElectronicDocument $document): void
  {
    try {
      $originalDocument = $document->originalDocument;
      if (!$originalDocument) {
        return;
      }

      $workOrder = null;
      if ($originalDocument->work_order_id) {
        $workOrder = ApWorkOrder::with(['parts.product'])->find($originalDocument->work_order_id);
      }

      if (!$workOrder) {
        return;
      }

      Log::info('🎯 [SIMULATION] ============ QUÉ VA A HACER ============', [
        'credit_note_id' => $document->id,
        'credit_note_number' => $document->full_number,
        'credit_note_type' => $document->sunat_concept_credit_note_type_id,
        'accion' => 'CREAR MOVIMIENTO DE DEVOLUCIÓN (RETURN_IN)',
      ]);

      // Obtener warehouse de la sede
      $warehouse = \App\Models\ap\maestroGeneral\Warehouse::where('sede_id', $workOrder->sede_id)
        ->where('is_physical_warehouse', true)
        ->where('status', true)
        ->first();

      if (!$warehouse) {
        Log::warning('🎯 [SIMULATION] No se encontró almacén para la sede', [
          'sede_id' => $workOrder->sede_id,
        ]);
        return;
      }

      // Simular qué productos se van a devolver
      foreach ($workOrder->parts as $part) {
        if (!$part->product_id || $part->is_traverse) {
          continue;
        }

        $stock = \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::where('product_id', $part->product_id)
          ->where('warehouse_id', $warehouse->id)
          ->first();

        if ($stock) {
          $quantityToAdd = $part->quantity_used;
          $expectedQuantityAfter = $stock->quantity + $quantityToAdd;
          $expectedAvailableAfter = $expectedQuantityAfter - $stock->reserved_quantity;

          Log::info('🎯 [SIMULATION] Producto que se devolverá', [
            'product_id' => $part->product_id,
            'product_code' => $part->product->code ?? 'N/A',
            'product_name' => $part->product->name ?? 'N/A',
            'quantity_to_add' => $quantityToAdd,
            'ACTUAL_quantity' => $stock->quantity,
            'ACTUAL_reserved' => $stock->reserved_quantity,
            'ACTUAL_available' => $stock->available_quantity,
            'ESPERADO_quantity_after' => $expectedQuantityAfter,
            'ESPERADO_reserved_after' => $stock->reserved_quantity . ' (NO debe cambiar)',
            'ESPERADO_available_after' => $expectedAvailableAfter,
          ]);
        }
      }

      Log::info('🎯 [SIMULATION] ========================================');
    } catch (Exception $e) {
      Log::error('❌ [SIMULATION] Error al simular', [
        'credit_note_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Loguear stock ANTES de procesar la nota de crédito
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function logStockBeforeProcessing(ElectronicDocument $document): void
  {
    try {
      $originalDocument = $document->originalDocument;
      if (!$originalDocument) {
        Log::warning('📊 [STOCK-BEFORE] No se encontró documento original', [
          'credit_note_id' => $document->id,
          'full_number' => $document->full_number,
        ]);
        return;
      }

      // Obtener OT relacionada
      $workOrder = null;
      if ($originalDocument->work_order_id) {
        $workOrder = ApWorkOrder::with(['parts.product'])->find($originalDocument->work_order_id);
      }

      if (!$workOrder) {
        Log::warning('📊 [STOCK-BEFORE] No se encontró orden de trabajo', [
          'credit_note_id' => $document->id,
          'full_number' => $document->full_number,
          'original_document_id' => $originalDocument->id,
        ]);
        return;
      }

      Log::info('📊 [STOCK-BEFORE] Estado del stock ANTES de contabilizar NC', [
        'credit_note_id' => $document->id,
        'credit_note_number' => $document->full_number,
        'credit_note_type' => $document->sunat_concept_credit_note_type_id,
        'work_order_id' => $workOrder->id,
        'work_order_correlative' => $workOrder->correlative,
        'original_invoice_id' => $originalDocument->id,
        'original_invoice_number' => $originalDocument->full_number,
      ]);

      // Obtener warehouse de la sede
      $warehouse = \App\Models\ap\maestroGeneral\Warehouse::where('sede_id', $workOrder->sede_id)
        ->where('is_physical_warehouse', true)
        ->where('status', true)
        ->first();

      if (!$warehouse) {
        Log::warning('📊 No se encontró almacén para la sede', [
          'sede_id' => $workOrder->sede_id,
        ]);
        return;
      }

      // Loguear cada repuesto de la OT
      foreach ($workOrder->parts as $part) {
        if (!$part->product_id || $part->is_traverse) {
          continue;
        }

        $stock = \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::where('product_id', $part->product_id)
          ->where('warehouse_id', $warehouse->id)
          ->first();

        if ($stock) {
          Log::info('📦 [STOCK-BEFORE] Producto en OT', [
            'product_id' => $part->product_id,
            'product_code' => $part->product->code ?? 'N/A',
            'product_name' => $part->product->name ?? 'N/A',
            'quantity_used_in_ot' => $part->quantity_used,
            'stock_quantity' => $stock->quantity,
            'stock_reserved_quantity' => $stock->reserved_quantity,
            'stock_available_quantity' => $stock->available_quantity,
            'warehouse_id' => $stock->warehouse_id,
          ]);
        } else {
          Log::warning('⚠️ [STOCK-BEFORE] No se encontró stock para producto', [
            'product_id' => $part->product_id,
            'product_code' => $part->product->code ?? 'N/A',
          ]);
        }
      }
    } catch (Exception $e) {
      Log::error('❌ [STOCK-BEFORE] Error al loguear stock antes', [
        'credit_note_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Loguear stock DESPUÉS de procesar la nota de crédito
   *
   * @param ElectronicDocument $document
   * @return void
   */
  private function logStockAfterProcessing(ElectronicDocument $document): void
  {
    try {
      $originalDocument = $document->originalDocument;
      if (!$originalDocument) {
        Log::warning('📊 [STOCK-AFTER] No se encontró documento original', [
          'credit_note_id' => $document->id,
          'full_number' => $document->full_number,
        ]);
        return;
      }

      // Obtener OT relacionada
      $workOrder = null;
      if ($originalDocument->work_order_id) {
        $workOrder = ApWorkOrder::with(['parts.product'])->find($originalDocument->work_order_id);
      }

      if (!$workOrder) {
        Log::warning('📊 [STOCK-AFTER] No se encontró orden de trabajo', [
          'credit_note_id' => $document->id,
          'full_number' => $document->full_number,
          'original_document_id' => $originalDocument->id,
        ]);
        return;
      }

      Log::info('📊 [STOCK-AFTER] Estado del stock DESPUÉS de contabilizar NC', [
        'credit_note_id' => $document->id,
        'credit_note_number' => $document->full_number,
        'credit_note_type' => $document->sunat_concept_credit_note_type_id,
        'work_order_id' => $workOrder->id,
        'work_order_correlative' => $workOrder->correlative,
        'original_invoice_id' => $originalDocument->id,
        'original_invoice_number' => $originalDocument->full_number,
      ]);

      // Obtener warehouse de la sede
      $warehouse = \App\Models\ap\maestroGeneral\Warehouse::where('sede_id', $workOrder->sede_id)
        ->where('is_physical_warehouse', true)
        ->where('status', true)
        ->first();

      if (!$warehouse) {
        Log::warning('📊 No se encontró almacén para la sede', [
          'sede_id' => $workOrder->sede_id,
        ]);
        return;
      }

      // Loguear cada repuesto de la OT
      foreach ($workOrder->parts as $part) {
        if (!$part->product_id || $part->is_traverse) {
          continue;
        }

        $stock = \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::where('product_id', $part->product_id)
          ->where('warehouse_id', $warehouse->id)
          ->first();

        if ($stock) {
          Log::info('📦 [STOCK-AFTER] Producto en OT', [
            'product_id' => $part->product_id,
            'product_code' => $part->product->code ?? 'N/A',
            'product_name' => $part->product->name ?? 'N/A',
            'quantity_used_in_ot' => $part->quantity_used,
            'stock_quantity' => $stock->quantity,
            'stock_reserved_quantity' => $stock->reserved_quantity,
            'stock_available_quantity' => $stock->available_quantity,
            'warehouse_id' => $stock->warehouse_id,
          ]);
        } else {
          Log::warning('⚠️ [STOCK-AFTER] No se encontró stock para producto', [
            'product_id' => $part->product_id,
            'product_code' => $part->product->code ?? 'N/A',
          ]);
        }
      }

      // Loguear movimientos de inventario creados
      $returnMovements = \App\Models\ap\postventa\gestionProductos\InventoryMovement::where('reference_type', ElectronicDocument::class)
        ->where('reference_id', $document->id)
        ->where('movement_type', \App\Models\ap\postventa\gestionProductos\InventoryMovement::TYPE_RETURN_IN)
        ->with(['details'])
        ->get();

      if ($returnMovements->isNotEmpty()) {
        foreach ($returnMovements as $movement) {
          Log::info('🔄 [STOCK-AFTER] Movimiento de devolución creado', [
            'movement_id' => $movement->id,
            'movement_number' => $movement->movement_number,
            'movement_type' => $movement->movement_type,
            'status' => $movement->status,
            'total_items' => $movement->total_items,
            'total_quantity' => $movement->total_quantity,
            'details_count' => $movement->details->count(),
          ]);

          foreach ($movement->details as $detail) {
            Log::info('📦 [STOCK-AFTER] Detalle de devolución', [
              'product_id' => $detail->product_id,
              'quantity' => $detail->quantity,
              'unit_cost' => $detail->unit_cost,
              'total_cost' => $detail->total_cost,
            ]);
          }
        }
      } else {
        Log::warning('⚠️ [STOCK-AFTER] No se encontraron movimientos de devolución', [
          'credit_note_id' => $document->id,
        ]);
      }
    } catch (Exception $e) {
      Log::error('❌ [STOCK-AFTER] Error al loguear stock después', [
        'credit_note_id' => $document->id,
        'error' => $e->getMessage(),
      ]);
    }
  }

  public function failed(Throwable $exception): void
  {
    Log::error('SyncAccountingStatusJob falló', ['error' => $exception->getMessage()]);
  }
}
