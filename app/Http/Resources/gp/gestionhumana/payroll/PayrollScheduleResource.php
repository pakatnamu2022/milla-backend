<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollScheduleResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'work_date' => $this->work_date,
      'hours_worked' => (float) $this->hours_worked,
      'extra_hours' => (float) $this->extra_hours,
      'total_hours' => $this->total_hours,
      'notes' => $this->notes,
      'status' => $this->status,

      // Relations
      'worker' => $this->worker ? [
        'id' => $this->worker->id,
        'full_name' => $this->worker->nombre_completo,
        'vat' => $this->worker->vat,
      ] : null,

      'work_type' => $this->workType ? new PayrollWorkTypeResource($this->workType) : null,

      'period' => $this->period ? [
        'id' => $this->period->id,
        'code' => $this->period->code,
        'name' => $this->period->name,
      ] : null,

      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
