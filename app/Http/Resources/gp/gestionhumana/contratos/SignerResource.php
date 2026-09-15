<?php

namespace App\Http\Resources\gp\gestionhumana\contratos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignerResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                => $this->id,
      'nombre'            => $this->nombre,
      'persona_id'        => $this->persona_id,
      'worker_name'       => $this->whenLoaded('worker', fn() => $this->worker?->nombre_completo),
      'sucursal_id'       => $this->sucursal_id,
      'sede_abreviatura'  => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura),
      'has_certificate'   => !empty($this->file),
      'has_key'           => !empty($this->key),
      'has_firmaimg'      => !empty($this->firmaimg),
      'fecha_vencimiento' => $this->fecha_vencimiento,
      'created_at'        => $this->created_at,
      'updated_at'        => $this->updated_at,
    ];
  }
}
