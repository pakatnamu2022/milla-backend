<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerStatusHistoryResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'empleado_id' => $this->empleado_id,
      'empleado'    => $this->whenLoaded('employee', fn() => [
        'id'     => $this->employee->id,
        'nombre' => $this->employee->nombre_completo,
        'vat'    => $this->employee->vat,
      ]),
      'fecha'       => $this->fecha,
      'estado'      => $this->estado,
      'motivo'      => $this->motivo,
      'sucursal_id' => $this->sucursal_id,
      'sede'        => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura),
      'write_id'    => $this->write_id,
      'created_at'  => $this->created_at,
      'updated_at'  => $this->updated_at,
    ];
  }
}
