<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollCalculationDetailResource extends JsonResource
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
      'concept_code' => $this->concept_code,
      'concept_name' => $this->concept_name,
      'type' => $this->type,
      'category' => $this->category,

      // Attendance fields
      'hour_type' => $this->hour_type,
      'hours' => $this->hours ? (float) $this->hours : null,
      'days_worked' => (int) $this->days_worked,
      'multiplier' => $this->multiplier ? (float) $this->multiplier : null,
      'use_shift' => (bool) $this->use_shift,

      // Tax/Insurance/Loan fields
      'base_amount' => $this->base_amount ? (float) $this->base_amount : null,
      'rate' => $this->rate ? (float) $this->rate : null,

      // Calculated values
      'hour_value' => (float) $this->hour_value,
      'amount' => (float) $this->amount,

      // Legacy formula fields
      'formula_used' => $this->formula_used,
      'variables_snapshot' => $this->variables_snapshot,
      'calculation_order' => (int) $this->calculation_order,

      // Relations
      'concept' => $this->concept ? [
        'id' => $this->concept->id,
        'code' => $this->concept->code,
        'name' => $this->concept->name,
        'category' => $this->concept->category,
      ] : null,

      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
