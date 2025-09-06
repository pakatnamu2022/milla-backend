<?php

namespace App\Http\Resources\ap\configuracionComercial\venta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApAccountingAccountPlanResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'cuenta' => $this->cuenta,
      'descripcion' => $this->descripcion,
      'tipo_cta_contable_id' => $this->tipo_cta_contable_id,
      'tipo_cuenta_contable' => $this->tipoCuenta->descripcion,
      'status' => $this->status,
    ];
  }
}
