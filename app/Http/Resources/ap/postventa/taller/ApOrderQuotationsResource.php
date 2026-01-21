<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderQuotationsResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'vehicle_id' => $this->vehicle_id,
      'client_id' => $this->client_id,
      'sede_id' => $this->sede_id,
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
      'has_sufficient_stock' => $this->when(
        isset($this->additional['checkStock']) && $this->additional['checkStock'],
        fn() => $this->checkSufficientStock()
      ),
      'output_generation_warehouse' => (bool)$this->output_generation_warehouse,
      'discard_reason' => $this->discardReason->description ?? null,
      'discarded_note' => $this->discarded_note,
      'discarded_by_name' => $this->discardedBy->name ?? null,
      'discarded_at' => $this->discarded_at ? $this->discarded_at->format('Y-m-d') : null,
      'supply_type' => $this->supply_type,
      'status' => $this->status,

      // Relations
      'details' => ApOrderQuotationDetailsResource::collection($this->details),
      'advances' => ElectronicDocumentResource::collection(
        $this->whenLoaded('advancesOrderQuotation', fn() => $this->advancesOrderQuotation->filter(fn($advance) => $advance->aceptada_por_sunat == 1))
      ),
      'client' => $this->client,
    ];
  }

  /**
   * Check if there is sufficient stock for all products in the quotation details
   *
   * @return bool
   */
  private function checkSufficientStock(): bool
  {
    // Get warehouse from sede
    $warehouse = Warehouse::where('sede_id', $this->sede_id)
      ->where('is_physical_warehouse', 1)
      ->where('status', 1)
      ->first();

    // If no warehouse found, return false
    if (!$warehouse) {
      return false;
    }

    // Get all product details from quotation
    $productDetails = $this->details->where('item_type', 'PRODUCT');

    // If no products, return true
    if ($productDetails->isEmpty()) {
      return true;
    }

    // Check stock for each product
    foreach ($productDetails as $detail) {
      // Skip if no product_id
      if (!$detail->product_id) {
        continue;
      }

      // Get stock for this product in this warehouse
      $stock = ProductWarehouseStock::where('warehouse_id', $warehouse->id)
        ->where('product_id', $detail->product_id)
        ->first();

      // If no stock record found or insufficient available quantity, return false
      if (!$stock || $stock->available_quantity < $detail->quantity) {
        return false;
      }
    }

    // All products have sufficient stock
    return true;
  }
}
