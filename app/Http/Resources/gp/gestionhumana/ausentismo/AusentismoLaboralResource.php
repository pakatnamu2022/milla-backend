<?php

namespace App\Http\Resources\gp\gestionhumana\ausentismo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AusentismoLaboralResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                => $this->id,
      'empleado_id'       => $this->empleado_id,
      'empleado'          => $this->whenLoaded('empleado', fn() => [
        'id'              => $this->empleado->id,
        'nombre_completo' => strtoupper($this->empleado->nombre_completo ?? ''),
        'vat'             => $this->empleado->vat,
      ]),
      'tipo_descanso'     => $this->whenLoaded('tipoDescanso', fn() => [
        'id'          => $this->tipoDescanso->id,
        'descripcion' => $this->tipoDescanso->descripcion,
      ]),
      'fecha_inicial'      => $this->fecha_inicial?->toDateString(),
      'fecha_fin'          => $this->fecha_fin?->toDateString(),
      'id_tipo_descanso'   => $this->id_tipo_descanso,
      'mes'                => $this->mes,
      'anio'               => $this->anio,
      'motivo'             => $this->motivo,
      'tipo_contingencia'  => $this->tipo_contingencia,
      'fecha_contingencia' => $this->fecha_contingencia?->toDateString(),
      'atencion'           => $this->atencion,
      'diagnostico'        => $this->diagnostico,
      'citt'               => $this->citt,
      'centro_atencion'    => $this->centro_atencion,
      'sede_id'            => $this->sede_id,
      'area_id'            => $this->area_id,
      'estado'             => $this->estado,
      'estado_aprobacion'  => $this->estado_aprobacion,
      'fecha_aprobacion'   => $this->fecha_aprobacion?->toDateTimeString(),
      'status_deleted'     => $this->status_deleted,
      'created_at'         => $this->created_at?->toDateTimeString(),
      'updated_at'         => $this->updated_at?->toDateTimeString(),
    ];
  }
}
