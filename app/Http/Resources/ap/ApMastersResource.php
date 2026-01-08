<?php

namespace App\Http\Resources\ap;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApMastersResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'description' => $this->description,
      'type' => $this->type,
      'status' => $this->status,
    ];
  }
}
