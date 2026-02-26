<?php

namespace App\Http\Resources\ap\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $data = [
      'id' => $this->id,
      'dyn_code' => $this->dyn_code,
      'description' => $this->description,
      'article_class_id' => $this->article_class_id ?? "",
      'article_class' => $this->articleClass->description ?? "-",
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->abreviatura,
      'type_operation_id' => $this->type_operation_id,
      'type_operation' => $this->typeOperation->description,
      'status' => $this->status,
      'is_received' => $this->is_received,
      'inventory_account' => $this->inventory_account,
      'counterparty_account' => $this->counterparty_account,
      'is_physical_warehouse' => $this->is_physical_warehouse,
      'parent_warehouse_id' => $this->parent_warehouse_id ?? "",
      'parent_warehouse_dyn_code' => $this->parentWarehouse ? $this->parentWarehouse->dyn_code : "-",
    ];

    // Add has_product field if it exists (for product availability queries)
    if (isset($this->has_product)) {
      $data['has_product'] = $this->has_product;
    }

    return $data;
  }
}
