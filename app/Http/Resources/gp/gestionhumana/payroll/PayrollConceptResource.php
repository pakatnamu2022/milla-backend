<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollConceptResource extends JsonResource
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
      'description' => $this->description,
      'type' => $this->type,
      'category' => $this->category,
      'formula' => $this->formula,
      'formula_description' => $this->formula_description,
      'is_taxable' => (bool) $this->is_taxable,
      'calculation_order' => (int) $this->calculation_order,
      'active' => (bool) $this->active,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
