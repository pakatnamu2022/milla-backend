<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignmentLeadershipResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'boss_id' => $this->id,
      'boss_name' => $this->nombre_completo,
      'assigned_workers' => $this->advisorsBoss->map(fn($worker) => [
        'id' => $worker->id,
        'name' => $worker->nombre_completo,
      ]),
    ];
  }
}
