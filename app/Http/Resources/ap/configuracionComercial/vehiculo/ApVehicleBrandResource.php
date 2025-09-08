<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use App\Http\Traits\HandlesFiles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleBrandResource extends JsonResource
{
  use HandlesFiles;

  public function toArray(Request $request): array
  {
    $data = [
      'id' => $this->id,
      'code' => $this->code,
      'dyn_code' => $this->dyn_code,
      'name' => $this->name,
      'description' => $this->description,
      'group_id' => $this->group_id,
      'group' => $this->group->description,
      'status' => $this->status,
    ];

    // Generar URLs de archivos
    if ($this->resource instanceof ApVehicleBrand) {
      $fileFields = ['logo', 'logo_min'];
      $urls = $this->generateFileUrls($this->resource, $fileFields);
      $data = array_merge($data, $urls);
    }

    return $data;
  }
}
