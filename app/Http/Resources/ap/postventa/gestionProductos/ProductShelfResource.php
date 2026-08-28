<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductShelfResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'warehouse_id' => $this->warehouse_id,
      'warehouse' => $this->warehouse?->description,
      'code' => $this->code,
      'label' => $this->label,
      'notes' => $this->notes,
      'status' => $this->status,
      'created_by' => $this->created_by,
      'creator' => $this->creator?->name,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}