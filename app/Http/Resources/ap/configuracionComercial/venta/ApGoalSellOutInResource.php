<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApGoalSellOutInResource extends JsonResource
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
      'year' => $this->year,
      'month' => $this->month,
      'period' => $this->year . '-' . $this->month,
      'goal' => $this->goal,
      'brand_id' => $this->brand_id,
      'brand' => $this->brand->name,
      'shop_id' => $this->shop_id,
      'shop' => $this->shop->description,
      'type' => $this->type,
      'total_goal' => $this->total_goal
    ];
  }
}
