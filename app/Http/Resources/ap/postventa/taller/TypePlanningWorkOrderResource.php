<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypePlanningWorkOrderResource extends JsonResource
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
      'description' => $this->description,
      'validate_receipt' => $this->validate_receipt,
      'validate_labor' => $this->validate_labor,
      'type_document' => $this->type_document,
      'status' => $this->status,
    ];
  }
}
