<?php

namespace App\Http\Resources\gp\gestionhumana\contratos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractTypeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'              => $this->id,
      'descripcion'     => $this->descripcion,
      'anios'           => $this->anios,
      'dias_vacaciones' => $this->dias_vacaciones,
      'created_at'      => $this->created_at,
      'updated_at'      => $this->updated_at,
    ];
  }
}
