<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacationResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                        => $this->id,
      'empleado_id'               => $this->empleado_id,
      'empleado'                  => $this->whenLoaded('employee', fn() => [
        'id'     => $this->employee->id,
        'nombre' => $this->employee->nombre_completo,
        'vat'    => $this->employee->vat,
      ]),
      'fecha_inicio'              => $this->fecha_inicio,
      'fecha_fin'                 => $this->fecha_fin,
      'tipo'                      => $this->tipo,
      'periodo_inicio'            => $this->periodo_inicio,
      'periodo_fin'               => $this->periodo_fin,
      'observacion'               => $this->observacion,
      'status_id'                 => $this->status_id,
      'status'                    => $this->whenLoaded('status', fn() => $this->status?->estado),
      'aprobacion_jefatura'       => $this->aprobacion_jefatura,
      'fecha_aprobacion_jefatura' => $this->fecha_aprobacion_jefatura,
      'user_jefatura_id'          => $this->user_jefatura_id,
      'aprobacion_rrhh'           => $this->aprobacion_rrhh,
      'fecha_aprobacion_rrhh'     => $this->fecha_aprobacion_rrhh,
      'user_id_rrhh'              => $this->user_id_rrhh,
      'sede_id'                   => $this->sede_id,
      'sede'                      => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura),
      'created_at'                => $this->created_at,
      'updated_at'                => $this->updated_at,
    ];
  }
}
