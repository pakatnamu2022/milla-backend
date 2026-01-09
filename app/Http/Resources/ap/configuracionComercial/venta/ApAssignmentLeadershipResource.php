<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignmentLeadershipResource extends JsonResource
{
  public static $wrap = null;

  public function toArray($request): array
  {
    $first = $this->first();

    return [
      'boss_id' => $first->boss->id,
      'boss_position' => $first->boss->position->name,
      'boss_name' => $first->boss->nombre_completo,
      'year' => $first->year,
      'month' => $first->month,
      'assigned_workers' => $this->map(function ($item) {
        return [
          'id' => $item->worker->id,
          'name' => $item->worker->nombre_completo,
        ];
      })->values(),
      'status' => $first->status,
    ];
  }
}
