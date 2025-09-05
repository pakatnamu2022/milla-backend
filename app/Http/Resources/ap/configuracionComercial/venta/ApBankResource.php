<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApBankResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'numero_cuenta' => $this->numero_cuenta,
      'cci' => $this->cci,
      'banco_id' => $this->banco_id,
      'banco' => $this->banco->descripcion,
      'descripcion' => $this->banco->descripcion . ' (' . $this->moneda->nombre . ')',
      'moneda_id' => $this->moneda_id,
      'moneda' => $this->moneda->nombre,
      'sede_id' => $this->sede_id,
      'sede' => $this->sede->suc_abrev,
      'status' => $this->status,
    ];
  }
}
