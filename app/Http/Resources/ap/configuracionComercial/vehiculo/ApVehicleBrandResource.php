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
      'codigo' => $this->codigo,
      'codigo_dyn' => $this->codigo_dyn,
      'grupo_id' => $this->grupo_id,
      'grupo' => $this->grupo->descripcion,
      'nombre' => $this->nombre,
      'descripcion' => $this->descripcion,
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
