<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodResource extends JsonResource
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
      'code' => $this->code,
      'name' => $this->name,
      'year' => (int) $this->year,
      'month' => (int) $this->month,
      'start_date' => $this->start_date,
      'end_date' => $this->end_date,
      'payment_date' => $this->payment_date,
      'status' => $this->status,
      'can_modify' => $this->canModify(),
      'can_calculate' => $this->canCalculate(),

      // Relations
      'company' => $this->company ? [
        'id' => $this->company->id,
        'name' => $this->company->name,
      ] : null,

      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
