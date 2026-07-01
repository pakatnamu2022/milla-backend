<?php

namespace App\Http\Resources\gp\gestionhumana\asistencias;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceExclusionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'person'     => WorkerResource::make($this->whenLoaded('person')),
      'reason'     => $this->reason,
      'active'     => $this->active,
      'created_by' => $this->created_by,
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
