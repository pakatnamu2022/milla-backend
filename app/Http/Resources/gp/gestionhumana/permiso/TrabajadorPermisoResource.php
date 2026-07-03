<?php

namespace App\Http\Resources\gp\gestionhumana\permiso;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrabajadorPermisoResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'             => $this->id,
      'partner_id'     => $this->partner_id,
      'empleado'       => $this->whenLoaded('empleado', fn() => [
        'id'              => $this->empleado->id,
        'nombre_completo' => strtoupper($this->empleado->nombre_completo ?? ''),
        'vat'             => $this->empleado->vat,
      ]),
      'fecha_inicio'   => $this->fecha_inicio?->toDateString(),
      'fecha_fin'      => $this->fecha_fin?->toDateString(),
      'c_motivo'       => $this->c_motivo,
      'sin_goce'       => $this->sin_goce,
      'write_id'       => $this->write_id,
      'sucursal_id'    => $this->sucursal_id,
      'status_deleted' => $this->status_deleted,
      'created_at'     => $this->created_at?->toDateTimeString(),
      'updated_at'     => $this->updated_at?->toDateTimeString(),
    ];
  }
}
