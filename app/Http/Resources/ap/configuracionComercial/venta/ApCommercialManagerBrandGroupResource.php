<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApCommercialManagerBrandGroupResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'brand_group_id' => $this->id,
      'brand_group' => $this->description,
      'commercial_managers' => $this->commercialManagers->map(fn($commercialManager) => [
        'id' => $commercialManager->id,
        'name' => $commercialManager->nombre_completo,
      ]),
    ];
  }
}
