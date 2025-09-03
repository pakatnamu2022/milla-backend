<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAssignBrandConsultantResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'anio' => $this->anio,
      'month' => $this->month,
      'periodo' => $this->anio . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT),
      'objetivo_venta' => $this->objetivo_venta,
      'status' => $this->status,
      'marca_id' => $this->marca->id,
      'marca' => $this->marca->nombre,
      'sede_id' => $this->sede->id,
      'sede' => $this->sede->abreviatura,
      'asesor_id' => $this->asesor->id,
      'asesor' => $this->asesor->nombre_completo,
    ];
  }
}
