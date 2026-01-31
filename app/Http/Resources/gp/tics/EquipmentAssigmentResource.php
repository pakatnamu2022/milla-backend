<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentAssigmentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'persona_id' => $this->persona_id,
      'worker_name' => $this->worker?->nombre_completo,
      'fecha' => $this->fecha,
      'status_id' => $this->status_id,
      'status_deleted' => $this->status_deleted,
      'write_id' => $this->write_id,
      'conformidad' => $this->conformidad,
      'fecha_conformidad' => $this->fecha_conformidad,
      'unassigned_at' => $this->unassigned_at,
      'items' => EquipmentItemAssigmentResource::collection($this->whenLoaded('items')),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
