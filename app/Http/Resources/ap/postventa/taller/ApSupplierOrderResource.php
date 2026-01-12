<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\maestroGeneral\TypeCurrencyResource;
use App\Http\Resources\ap\maestroGeneral\WarehouseResource;
use App\Http\Resources\gp\gestionsistema\UserCompleteResource;
use App\Http\Resources\gp\maestroGeneral\SedeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApSupplierOrderResource extends JsonResource
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
      'supplier_id' => $this->supplier_id,
      'sede_id' => $this->sede_id,
      'warehouse_id' => $this->warehouse_id,
      'type_currency_id' => $this->type_currency_id,
      'created_by' => $this->created_by,
      'order_date' => $this->order_date,
      'order_number' => $this->order_number,
      'supply_type' => $this->supply_type,
      'total_amount' => $this->total_amount,
      'is_take' => $this->is_take,
      'status' => $this->status,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'has_invoice' => !is_null($this->ap_purchase_order_id),

      // Relationships
      'supplier' => new BusinessPartnersResource($this->whenLoaded('supplier')),
      'sede' => new SedeResource($this->whenLoaded('sede')),
      'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
      'type_currency' => new TypeCurrencyResource($this->whenLoaded('typeCurrency')),
      'created_by_user' => new UserCompleteResource($this->whenLoaded('createdBy')),
      'details' => ApSupplierOrderDetailsResource::collection($this->whenLoaded('details')),
    ];
  }
}
