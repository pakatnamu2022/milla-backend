<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationModelResource extends JsonResource
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
      'categories' => $this->categories,
      'leadership_weight' => $this->leadership_weight,
      'self_weight' => $this->self_weight,
      'par_weight' => $this->par_weight,
      'report_weight' => $this->report_weight,

      // Relationships
      'category_details' => SimpleHierarchicalCategoryResource::collection($this->categoriesData()),
    ];
  }
}
