<?php

namespace App\Http\Resources\gp\gestionhumana\asistencias;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceCodeMappingResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'emp_code'   => $this->emp_code,
      'vat'        => $this->vat,
      'note'       => $this->note,
      'created_by' => $this->created_by,
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
