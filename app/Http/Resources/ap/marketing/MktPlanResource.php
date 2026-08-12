<?php

namespace App\Http\Resources\ap\marketing;

use App\Models\ap\marketing\MktPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktPlanResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'brand_id'    => $this->brand_id,
      'brand'       => $this->whenLoaded('brand', fn() => [
        'id'   => $this->brand->id,
        'name' => $this->brand->name,
      ]),
      'name'        => $this->name,
      'concept'     => $this->concept,
      'year'        => $this->year,
      'description' => $this->description,
      'status'       => $this->status,
      'status_label' => MktPlan::STATUS_LABELS[$this->status] ?? $this->status,
      'budgets'     => MktBudgetResource::collection($this->whenLoaded('budgets')),
      'created_by'  => $this->created_by,
      'updated_by'  => $this->updated_by,
      'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'  => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
