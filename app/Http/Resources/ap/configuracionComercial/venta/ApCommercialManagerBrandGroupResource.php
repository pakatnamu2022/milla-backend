<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApCommercialManagerBrandGroupResource extends JsonResource
{
  public static $wrap = null;

  public function toArray(Request $request): array
  {
    $first = $this->first();

    return [
      'brand_group_id' => $first->brandGroup->id,
      'brand_group' => $first->brandGroup->description,
      'year' => $first->year,
      'month' => $first->month,
      'commercial_managers' => $this->map(function ($item) {
        return [
          'id' => $item->commercialManager->id,
          'name' => $item->commercialManager->nombre_completo,
        ];
      })->values(),
      'status' => $first->status,
    ];
  }
}
