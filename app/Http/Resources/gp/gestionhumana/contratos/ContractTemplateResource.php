<?php

namespace App\Http\Resources\gp\gestionhumana\contratos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractTemplateResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'nombre'      => $this->nombre,
      'descripcion' => $this->descripcion,
      'contenido'   => $this->contenido,
      'created_at'  => $this->created_at,
      'updated_at'  => $this->updated_at,
    ];
  }
}
