<?php

namespace App\Http\Resources\ap\postventa\gestionProductos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'status' => $this->status,
      'type_id' => $this->type_id,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
