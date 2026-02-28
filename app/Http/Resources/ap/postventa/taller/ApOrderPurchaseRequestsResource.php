<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\compras\PurchaseOrderResource;
use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderPurchaseRequestsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'request_number' => $this->request_number,
      'ap_order_quotation_id' => $this->ap_order_quotation_id,
      'purchase_order_id' => $this->purchase_order_id,
      'warehouse_id' => $this->warehouse_id,
      'warehouse_dyn_code' => $this->warehouse ? $this->warehouse->dyn_code : "-",
      'requested_date' => $this->requested_date,
      'ordered_date' => $this->ordered_date,
      'received_date' => $this->received_date,
      'advisor_notified' => $this->advisor_notified,
      'notified_at' => $this->notified_at,
      'observations' => $this->observations,
      'status' => $this->status,
      'supplier_order_numbers' => $this->getSupplierOrderNumbers(),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'requested_by' => $this->requestedBy ? $this->requestedBy->name : null,
      'supply_type' => $this->supply_type,

      // Relationships
      'ap_order_quotation' => new ApOrderQuotationsResource($this->apOrderQuotation),
      'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
      'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
      'details' => ApOrderPurchaseRequestDetailsResource::collection($this->whenLoaded('details')),
    ];
  }

  /**
   * Get supplier order numbers associated with this purchase request
   */
  private function getSupplierOrderNumbers(): array
  {
    // Get all unique supplier orders through the details
    return $this->details()
      ->with('supplierOrders')
      ->get()
      ->pluck('supplierOrders')
      ->flatten()
      ->unique('id')
      ->pluck('order_number')
      ->values()
      ->toArray();
  }
}
