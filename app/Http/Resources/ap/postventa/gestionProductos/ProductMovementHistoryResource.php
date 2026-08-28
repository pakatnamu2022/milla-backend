<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\compras\SupplierCreditNote;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\postventa\gestionProductos\TransferReception;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Optimized resource for product movement history (kardex)
 * Only includes minimal data needed for the frontend table
 */
class ProductMovementHistoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      // Movement basic info
      'id' => $this->id,
      'movement_number' => $this->movement_number,
      'movement_number_dyn' => $this->movement_number_dyn,
      'movement_type' => $this->movement_type,
      'movement_date' => $this->movement_date,
      'is_inbound' => $this->is_inbound,
      'is_outbound' => $this->is_outbound,

      // Warehouse info - only displayed fields
      'warehouse_origin' => $this->warehouse ? [
        'description' => $this->warehouse->description,
        'dyn_code' => $this->warehouse->dyn_code,
      ] : null,

      'warehouse_destination' => $this->warehouseDestination ? [
        'description' => $this->warehouseDestination->description,
        'dyn_code' => $this->warehouseDestination->dyn_code,
      ] : null,

      // Reference data - formatted per type
      'reference_type' => $this->reference_type,
      'reference' => $this->formatMinimalReference(),

      // Electronic document - for sales and returns
      'electronic_document' => $this->formatMinimalElectronicDocument(),

      // User info
      'user_name' => $this->user?->name,

      // Additional info
      'notes' => $this->notes,

      // Kardex quantities
      'quantity_in' => $this->quantity_in ?? 0,
      'quantity_out' => $this->quantity_out ?? 0,
      'balance' => $this->balance ?? 0,
    ];
  }

  /**
   * Format electronic document with only the fields used by frontend
   * Used for: SALE, RETURN_IN movements
   */
  private function formatMinimalElectronicDocument(): ?array
  {
    if (!$this->electronicDocument) {
      return null;
    }

    $doc = $this->electronicDocument;

    return [
      'full_number' => $doc->full_number,
      'status' => $doc->status,
      'cliente_denominacion' => $doc->cliente_denominacion,
      'cliente_numero_de_documento' => $doc->cliente_numero_de_documento,
      'credit_note_id' => $doc->credit_note_id,
      'credit_note_number' => $doc->creditNote?->full_number,
    ];
  }

  /**
   * Format reference with minimal data based on movement type
   * Only includes fields that are actually displayed in the frontend table
   */
  private function formatMinimalReference(): mixed
  {
    if (!$this->reference) {
      return null;
    }

    $type = $this->reference_type;

    return match ($type) {
      PurchaseReception::class => $this->formatPurchaseReceptionReference(),
      ShippingGuides::class => $this->formatShippingGuideReference(),
      TransferReception::class => $this->formatTransferReceptionReference(),
      ApInternalNote::class => $this->formatInternalNoteReference(),
      ApWorkOrderParts::class => $this->formatWorkOrderPartsReference(),
      ApOrderQuotations::class => $this->formatOrderQuotationReference(),
      SupplierCreditNote::class => $this->formatSupplierCreditNoteReference(),
      default => null, // ApWorkOrder and other types rely only on electronic_document
    };
  }

  /**
   * PURCHASE_RECEPTION - Shows supplier info and invoice details
   */
  private function formatPurchaseReceptionReference(): array
  {
    /** @var PurchaseReception $reception */
    $reception = $this->reference;
    $purchaseOrder = $reception->purchaseOrder;

    if (!$purchaseOrder) {
      return [
        'supplier_name' => $reception->supplier_name,
        'supplier_num_doc' => $reception->supplier_num_doc,
      ];
    }

    return [
      'purchase_order' => [
        'supplier' => $purchaseOrder->supplier?->full_name,
        'supplier_num_doc' => $purchaseOrder->supplier?->num_doc,
        'invoice_series' => $purchaseOrder->invoice_series,
        'invoice_number' => $purchaseOrder->invoice_number,
        'invoice_dynamics' => $purchaseOrder->invoice_dynamics,
        'credit_note_dynamics' => $purchaseOrder->credit_note_dynamics,
        'status' => $purchaseOrder->status,
      ],
    ];
  }

  /**
   * TRANSFER_OUT - Shows destination warehouse and shipping guide
   * Can also be used for TRANSFER_IN when reference is ShippingGuides
   */
  private function formatShippingGuideReference(): array
  {
    /** @var ShippingGuides $guide */
    $guide = $this->reference;

    return [
      'document_number' => $guide->document_number,
      'is_annulled' => $guide->is_annulled,
      'receiver_name' => $guide->receiver_name,
      'receiver_establishment' => $guide->receiverEstablishment ? [
        'description' => $guide->receiverEstablishment->description,
      ] : null,
      'transmitter_establishment' => $guide->transmitterEstablishment ? [
        'description' => $guide->transmitterEstablishment->description,
      ] : null,
    ];
  }

  /**
   * TRANSFER_IN - Shows origin warehouse and shipping guide
   */
  private function formatTransferReceptionReference(): array
  {
    /** @var TransferReception $reception */
    $reception = $this->reference;
    $guide = $reception->shippingGuide;

    if (!$guide) {
      return [];
    }

    return [
      'shipping_guide' => [
        'document_number' => $guide->document_number,
        'transmitter_establishment' => $guide->transmitterEstablishment ? [
          'description' => $guide->transmitterEstablishment->description,
        ] : null,
      ],
    ];
  }

  /**
   * ADJUSTMENT_OUT/IN or SALE - Internal note (from workshop)
   */
  private function formatInternalNoteReference(): array
  {
    /** @var ApInternalNote $note */
    $note = $this->reference;

    return [
      'number' => $note->number,
      'work_order_correlative' => $note->work_order_correlative,
    ];
  }

  /**
   * ADJUSTMENT_OUT - Workshop parts requisition
   */
  private function formatWorkOrderPartsReference(): array
  {
    /** @var ApWorkOrderParts $part */
    $part = $this->reference;

    return [
      'work_order_correlative' => $part->workOrder?->correlative,
    ];
  }

  /**
   * ADJUSTMENT_OUT - Quotation (counter sale)
   */
  private function formatOrderQuotationReference(): array
  {
    /** @var ApOrderQuotations $quotation */
    $quotation = $this->reference;

    return [
      'quotation_number' => $quotation->quotation_number,
      'client' => $quotation->client ? [
        'full_name' => $quotation->client->full_name,
      ] : null,
    ];
  }

  /**
   * RETURN_OUT - Supplier credit note (return to supplier)
   */
  private function formatSupplierCreditNoteReference(): array
  {
    /** @var SupplierCreditNote $creditNote */
    $creditNote = $this->reference;
    $purchaseOrder = $creditNote->purchaseOrder;

    return [
      'credit_note_number' => $creditNote->credit_note_number,
      'purchase_order' => $purchaseOrder ? [
        'supplier' => $purchaseOrder->supplier?->full_name,
        'supplier_num_doc' => $purchaseOrder->supplier?->num_doc,
        'invoice_series' => $purchaseOrder->invoice_series,
        'invoice_number' => $purchaseOrder->invoice_number,
        'invoice_dynamics' => $purchaseOrder->invoice_dynamics,
      ] : null,
    ];
  }
}
