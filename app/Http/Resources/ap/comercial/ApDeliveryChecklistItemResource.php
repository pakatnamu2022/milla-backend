<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApDeliveryChecklistItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                   => $this->id,
      'delivery_checklist_id' => $this->delivery_checklist_id,
      'source'               => $this->source,
      'source_id'            => $this->source_id,
      'description'          => $this->description,
      'quantity'             => $this->quantity,
      'unit'                 => $this->unit,
      'is_confirmed'         => $this->is_confirmed,
      'observations'         => $this->observations,
      'sort_order'           => $this->sort_order,
    ];
  }
}
