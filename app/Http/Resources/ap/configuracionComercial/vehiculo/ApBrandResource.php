<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use App\Http\Traits\HandlesFiles;
use App\Models\ap\configuracionComercial\vehiculo\ApBrand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApBrandResource extends JsonResource
{
  use HandlesFiles;

  public function toArray($request)
  {
    $data = [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'codigo_dyn' => $this->codigo_dyn,
      'grupo_id' => $this->grupo_id,
      'grupo' => $this->grupo->name,
      'name' => $this->name,
      'descripcion' => $this->descripcion,
      'status' => $this->status,
    ];

    // Generar URLs de archivos
    if ($this->resource instanceof ApBrand) {
      $fileFields = ['logo', 'logo_min'];
      $urls = $this->generateFileUrls($this->resource, $fileFields);
      $data = array_merge($data, $urls);
    }

    return $data;
  }
}
