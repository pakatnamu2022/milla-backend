<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApReceivingChecklistResource extends JsonResource
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
      'receiving_id' => $this->receiving_id,
      'receiving_description' => $this->receiving->description,
      'quantity' => $this->quantity,
    ];
  }
}
