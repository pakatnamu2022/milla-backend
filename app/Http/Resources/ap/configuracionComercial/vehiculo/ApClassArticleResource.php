<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApClassArticleResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'dyn_code' => $this->dyn_code,
      'description' => $this->description,
      'account' => $this->account,
      'type_operation_id' => $this->type_operation_id ?? "",
      'type_operation_description' => $this->typeOperation->description ?? "",
      'status' => $this->status
    ];
  }
}
