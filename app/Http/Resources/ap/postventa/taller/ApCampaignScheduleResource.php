<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Resources\Json\JsonResource;

class ApCampaignScheduleResource extends JsonResource
{
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'sede_id' => $this->sede_id,
      'sede' => [
        'id' => $this->sede?->id,
        'name' => $this->sede?->name,
        'abreviatura' => $this->sede?->abreviatura,
      ],
      'worker_id' => $this->worker_id,
      'worker' => [
        'id' => $this->worker?->id,
        'nombre_completo' => $this->worker?->nombre_completo,
        'num_doc' => $this->worker?->num_doc,
      ],
      'date' => $this->date?->format('Y-m-d'),
      'created_by' => $this->created_by,
      'creator' => [
        'id' => $this->creator?->id,
        'name' => $this->creator?->name,
      ],
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}