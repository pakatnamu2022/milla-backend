<?php

namespace App\Http\Resources\gp\gestionhumana\asistencias;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSyncResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'zkbio_transaction_id' => $this->zkbio_transaction_id,
      'person_id' => $this->person_id,
      'emp_code' => $this->emp_code,
      'full_name' => strtoupper($this->person?->nombre_completo ?? $this->full_name),
      'date' => $this->date?->toDateString(),
      'mark_type' => $this->mark_type,
      'time' => $this->time,
      'area' => $this->area,
      'punch_state_original' => $this->punch_state_original,
      'synced_at' => $this->synced_at?->toDateTimeString(),
    ];
  }
}
