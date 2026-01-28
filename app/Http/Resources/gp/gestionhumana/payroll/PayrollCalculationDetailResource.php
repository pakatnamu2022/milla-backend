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
      'formula_used' => $this->formula_used,
      'variables_snapshot' => $this->variables_snapshot,
      'calculated_amount' => (float) $this->calculated_amount,
      'final_amount' => (float) $this->final_amount,
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
