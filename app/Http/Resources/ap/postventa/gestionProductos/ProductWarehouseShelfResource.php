<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductWarehouseShelfResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'product_warehouse_stock_id' => $this->product_warehouse_stock_id,
      'product_shelf_id' => $this->product_shelf_id,
      'position' => $this->position,
      'product' => [
        'id' => $this->productWarehouseStock?->product_id,
        'code' => $this->productWarehouseStock?->product?->code,
        'name' => $this->productWarehouseStock?->product?->name,
        'quantity' => $this->productWarehouseStock?->quantity,
        'available_quantity' => $this->productWarehouseStock?->available_quantity,
        'stock_status' => $this->productWarehouseStock?->stock_status,
      ],
      'warehouse' => $this->productWarehouseStock?->warehouse?->description,
      'shelf' => $this->productShelf?->label,
      'shelf_code' => $this->productShelf?->code,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}