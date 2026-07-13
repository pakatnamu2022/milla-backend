<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\comercial\ShippingGuidesResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\DiscountRequestsOrderQuotation;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\GeneralMaster;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderQuotationsResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $includeInvoicePreview = $this->relationLoaded('details');
    $invoicePreviewData = $includeInvoicePreview ? $this->getInvoicePreview() : null;

    return [
      'id' => $this->id,
      'parent_quotation_id' => $this->parent_quotation_id,
      'shipping_guide_id' => $this->shipping_guide_id,
      'was_segmented' => $this->segmentedQuotations->count() > 0,
      'vehicle_id' => $this->vehicle_id,
      'client_id' => $this->client_id,
      'sede_id' => $this->sede_id,
      'mileage' => $this->mileage,
      'warehouse_id' => Warehouse::getPhysicalWarehouseForPostsale($this->sede_id)?->id,
      'plate' => $this->vehicle ? $this->vehicle->plate : "-",
      'vehicle' => new VehiclesResource($this->vehicle),
      'quotation_number' => $this->quotation_number,
      'subtotal' => (float)$this->subtotal,
      'discount_percentage' => (float)$this->discount_percentage,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'total_amount' => (float)$this->total_amount,
      'validity_days' => $this->validity_days,
      'quotation_date' => $this->quotation_date,
      'expiration_date' => $this->expiration_date,
      'collection_date' => $this->collection_date,
      'observations' => $this->observations,
      'notes' => $this->notes,
      'currency_id' => $this->currency_id,
      'type_currency' => $this->typeCurrency,
      'exchange_rate' => (float)$this->exchange_rate,
      'op_gravada' => (float)($this->subtotal - $this->discount_amount),
      'created_by' => $this->created_by,
      'created_by_name' => $this->createdBy ? $this->createdBy->name : null,
      'is_take' => (bool)$this->is_take,
      'area_id' => $this->area_id,
      'has_invoice_generated' => (bool)$this->has_invoice_generated,
      'is_fully_paid' => (bool)$this->is_fully_paid,
      'invoice_to' => $this->invoice_to,
      'has_sufficient_stock' => $this->when(
        isset($this->additional['checkStock']) && $this->additional['checkStock'],
        fn() => $this->checkSufficientStock()
      ),
      'output_generation_warehouse' => (bool)$this->output_generation_warehouse,
      'discard_reason' => $this->discardReason->description ?? null,
      'discarded_note' => $this->discarded_note,
      'discarded_by_name' => $this->discardedBy->name ?? null,
      'discarded_at' => $this->discarded_at,
      'supply_type' => $this->supply_type,
      'customer_signature_delivery_url' => $this->customer_signature_delivery_url,
      'delivery_document_number' => $this->delivery_document_number,
      'chief_approval_by' => $this->chief_approval_by,
      'manager_approval_by' => $this->manager_approval_by,
      'chief_approval_by_name' => $this->chiefApprovalBy ? $this->chiefApprovalBy->name : null,
      'manager_approval_by_name' => $this->managerApprovalBy ? $this->managerApprovalBy->name : null,
      'status' => $this->status,
      'cost_man_hours' => $this->when(
        isset($this->additional['includeCostManHours']) && $this->additional['includeCostManHours'],
        fn() => $this->vehicle->is_heavy
          ? GeneralMaster::find(GeneralMaster::COST_PER_MAN_HOUR_VP_ID)->value
          : GeneralMaster::find(GeneralMaster::COST_PER_MAN_HOUR_VL_ID)->value
      ),
      'is_requested_by_management' => $this->is_requested_by_management,
      'emails_sent_count' => $this->emails_sent_count,
      'confirmed_at' => $this->confirmed_at,
      'confirmation_channel' => $this->confirmation_channel,
      'confirmation_ip' => $this->when(
        isset($this->additional['includeConfirmationData']) && $this->additional['includeConfirmationData'],
        $this->confirmation_ip
      ),
      'confirmation_metadata' => $this->when(
        isset($this->additional['includeConfirmationData']) && $this->additional['includeConfirmationData'],
        $this->confirmation_metadata
      ),

      // Relations
      'details' => ApOrderQuotationDetailsResource::collection($this->details),
      'invoice_to_client' => $this->whenLoaded('invoiceTo', fn() => BusinessPartnersResource::make($this->invoiceTo)),
      'vouchers' => $this->when(
        $this->relationLoaded('advancesOrderQuotation'),
        fn() => $this->getDocumentsTree()
      ),
      'payment_summary' => $this->when(
        $this->relationLoaded('advancesOrderQuotation'),
        fn() => $this->getPaymentSummary()
      ),
      'items_invoice' => $this->when($includeInvoicePreview, fn() => $invoicePreviewData['items_invoice']),
      'invoice_preview' => $this->when($includeInvoicePreview, fn() => $invoicePreviewData['invoice_preview']),
      'client' => $this->client,
      'has_management_discount' => $this->discountRequests && $this->discountRequests->where('status', DiscountRequestsOrderQuotation::STATUS_APPROVED)->isNotEmpty(),
      'shipping_guide' => $this->when('shippingGuide', fn() => new ShippingGuidesResource($this->shippingGuide)),
    ];
  }

  /**
   * Check if there is sufficient stock for all products in the quotation details
   *
   * Lógica según estado de confirmación y supply_type:
   *
   * CONFIRMADA:
   *   - STOCK: valida quantity (físico) Y reserved_quantity (reservado)
   *   - NO STOCK (LOCAL/CENTRAL/IMPORTACION): valida available_quantity (libre actual)
   *
   * NO CONFIRMADA:
   *   - STOCK: valida available_quantity (para saber si se puede reservar)
   *   - NO STOCK: valida available_quantity (libre actual)
   *
   * @return bool
   */
  private function checkSufficientStock(): bool
  {
    // Get warehouse from sede
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($this->sede_id);

    // If no warehouse found, return false
    if (!$warehouse) {
      return false;
    }

    // Get all product details from quotation
    $productDetails = $this->details
      ->where('item_type', 'PRODUCT')
      ->where('product_id', '!=', null);

    // If no products, return true
    if ($productDetails->isEmpty()) {
      return true;
    }

    // Determinar si la cotización ya está confirmada
    $isConfirmed = !is_null($this->confirmed_at);

    // Check stock for each product
    foreach ($productDetails as $detail) {
      // Get stock for this product in this warehouse
      $stock = ProductWarehouseStock::where('warehouse_id', $warehouse->id)
        ->where('product_id', $detail->product_id)
        ->first();

      if (!$stock) {
        return false;
      }

      // Validación según confirmación y supply_type
      if ($isConfirmed && $detail->supply_type === 'STOCK') {
        // Cotización confirmada + STOCK: validar físico Y reservado
        if ($stock->quantity < $detail->quantity || $stock->reserved_quantity < $detail->quantity) {
          return false;
        }
      } else {
        // Cualquier otro caso: validar stock disponible (libre)
        if ($stock->available_quantity < $detail->quantity) {
          return false;
        }
      }
    }

    // All products have sufficient stock
    return true;
  }
}
