<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApCampaignResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'             => $this->id,
      'area_id'        => $this->area_id,
      'area'           => $this->area ? ['id' => $this->area->id, 'description' => $this->area->description, 'type' => $this->area->type] : null,
      'code'           => $this->code,
      'name'           => $this->name,
      'description'    => $this->description,
      'start_date'     => $this->start_date?->format('Y-m-d'),
      'end_date'       => $this->end_date?->format('Y-m-d'),
      'discount_type'  => $this->discount_type,
      'discount_value' => $this->discount_value,
      'status'         => $this->status,
      'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
