<?php

namespace App\Http\Resources\gp\gestionhumana\asistencias;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceExclusionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'person_id'  => $this->person_id,
      'person'     => $this->whenLoaded('person', fn() => [
        'id'             => $this->person->id,
        'nombre_completo'=> $this->person->nombre_completo,
        'vat'            => $this->person->vat,
      ]),
      'reason'     => $this->reason,
      'active'     => $this->active,
      'created_by' => $this->created_by,
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
