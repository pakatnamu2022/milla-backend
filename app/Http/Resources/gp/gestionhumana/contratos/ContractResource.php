<?php

namespace App\Http\Resources\gp\gestionhumana\contratos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                       => $this->id,
      'empleado_id'              => $this->empleado_id,
      'trabajador'               => $this->whenLoaded('worker', fn() => $this->worker?->nombre_completo),
      'tipo_contrato_id'         => $this->tipo_contrato_id,
      'tipo_contrato'            => $this->whenLoaded('contractType', fn() => $this->contractType?->descripcion),
      'template_contrato_id'     => $this->template_contrato_id,
      'plantilla'                => $this->whenLoaded('contractTemplate', fn() => $this->contractTemplate?->nombre),
      'sede_id'                  => $this->sede_id,
      'sede'                     => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura ?? $this->sede?->razon_social),
      'cargo_id'                 => $this->cargo_id,
      'cargo'                    => $this->whenLoaded('position', fn() => $this->position?->name),
      'sueldo'                   => $this->sueldo,
      'fecha_inicio_actividades' => $this->fecha_inicio_actividades?->format('Y-m-d'),
      'fecha_inicio_contrato'    => $this->fecha_inicio_contrato?->format('Y-m-d'),
      'fecha_fin_contrato'       => $this->fecha_fin_contrato?->format('Y-m-d'),
      'observacion'              => $this->observacion,
      'grupo_contrato'           => $this->grupo_contrato,
      'contrato_principal'       => $this->contrato_principal,
      'convenio'                 => $this->convenio,
      'lote'                     => $this->lote,
      'firmante_id'              => $this->firmante_id,
      'firmante'                 => $this->whenLoaded('signer', fn() => $this->signer?->nombre),
      'firmante_sec_id'          => $this->firmante_sec_id,
      'firmante_secundario'      => $this->whenLoaded('secondarySigner', fn() => $this->secondarySigner?->nombre),
      'solicitar_firma'          => (bool) $this->solicitar_firma,
      'fecha_solicitud'          => $this->fecha_solicitud,
      'conformidad_rrhh'         => (bool) $this->conformidad_rrhh,
      'fecha_aprobacion_rrhh'    => $this->fecha_aprobacion_rrhh,
      'confirmacion_firmante'    => (bool) $this->confirmacion_firmante,
      'fecha_confirmacion_firma' => $this->fecha_confirmacion_firma,
      'estado_envio_email'       => (bool) $this->estado_envio_email,
      'fecha_envio_email'        => $this->fecha_envio_email,
      'conformidad_lectura'      => (bool) $this->conformidad_lectura,
      'fecha_lectura'            => $this->fecha_lectura,
      'created_at'               => $this->created_at,
      'updated_at'               => $this->updated_at,
    ];
  }
}
