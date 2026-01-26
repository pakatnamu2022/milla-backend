<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollFormulaVariableResource extends JsonResource
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
      'value' => $this->value !== null ? (float) $this->value : null,
      'source_field' => $this->source_field,
      'formula' => $this->formula,
      'active' => (bool) $this->active,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
