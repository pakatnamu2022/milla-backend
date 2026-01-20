<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignmentLeadershipItemResource extends JsonResource
{
  public static $wrap = null;

  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'boss_id' => $this->boss->id,
      'boss_name' => $this->boss->nombre_completo,
      'boss_position' => $this->boss->position->name ?? null,
      'worker_id' => $this->worker->id,
      'worker_name' => $this->worker->nombre_completo,
      'year' => $this->year,
      'month' => $this->month,
      'status' => $this->status,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
