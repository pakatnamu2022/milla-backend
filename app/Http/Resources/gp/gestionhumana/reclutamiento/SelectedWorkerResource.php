<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\SelectedWorker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelectedWorkerResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                              => $this->id,
      'nombre_completo'                 => $this->nombre_completo,
      'vat'                             => $this->vat,
      'sede_id'                         => $this->sede_id,
      'sede'                            => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura),
      'area_id'                         => $this->area_id,
      'area'                            => $this->whenLoaded('area', fn() => $this->area?->name),
      'cargo_id'                        => $this->cargo_id,
      'cargo'                           => $this->whenLoaded('position', fn() => $this->position?->name),
      'centro_costo_id'                 => $this->centro_costo_id,
      'proceso_postulacion_id'          => $this->proceso_postulacion_id,
      'proceso'                         => $this->whenLoaded('process', fn() => $this->process?->nombre_postulacion),
      'tipo_trabajador_id'              => $this->tipo_trabajador_id,
      'estado_trabajador'               => $this->tipo_trabajador_id == Applicant::TIPO_CONTRATADO ? 'Contratado' : 'Seleccionado',
      'jefe_id'                         => $this->jefe_id,
      'jefe'                            => $this->whenLoaded('boss', fn() => $this->boss?->nombre_completo),
      'supervisor_id'                   => $this->supervisor_id,
      'supervisor'                      => $this->whenLoaded('supervisor', fn() => $this->supervisor?->nombre_completo),
      'motivo_status'                   => $this->motivo_status,
      'fecha_inicio'                    => $this->fecha_inicio?->format('Y-m-d'),
      'presupuesto'                     => $this->presupuesto,
      'sueldo'                          => $this->sueldo,
      'carta_oferta'                    => $this->carta_oferta,
      'status_carta_oferta_id'          => $this->status_carta_oferta_id,
      'carta_oferta_firmada'            => (int) $this->status_carta_oferta_id === SelectedWorker::STATUS_CARTA_OFERTA_COMPLETADO,
      'status_envio_mail_carta_oferta'  => $this->status_envio_mail_carta_oferta,
      'fecha_envio_mail_carta_oferta'   => $this->fecha_envio_mail_carta_oferta,
      'status_id'                       => $this->status_id,
      'estado_altabaja'                 => $this->status_id == 22 ? 'Alta' : ($this->status_id == 23 ? 'Baja' : null),
      'has_user'                        => $this->whenLoaded('user', fn() => $this->user !== null),
      'cuenta_interbancaria_cts'        => $this->cuenta_interbancaria_cts,
      'cuenta_interbancaria_haberes'    => $this->cuenta_interbancaria_haberes,
      'cta_haberes'                     => $this->cta_haberes,
      'entidad_haberes'                 => $this->entidad_haberes,
      'cta_cts'                         => $this->cta_cts,
      'entidad_cts'                     => $this->entidad_cts,
      'vidaley'                         => $this->vidaley,
      'estado_sctr'                     => $this->estado_sctr,
      'entidad_sctr'                    => $this->entidad_sctr,
      'essaludvida'                     => $this->essaludvida,
      'escolaridad'                     => $this->escolaridad,
      'monto_escolaridad'               => $this->monto_escolaridad,
      'asignacion'                      => $this->asignacion,
      'sis_pensiones_id'                => $this->sis_pensiones_id,
      'cuspp'                           => $this->cuspp,
      'fecha_ingreso_afp_snp'           => $this->fecha_ingreso_afp_snp?->format('Y-m-d'),
      'relatives'                       => RelativeResource::collection($this->whenLoaded('relatives')),
      'work_experiences'                => WorkExperienceResource::collection($this->whenLoaded('workExperiences')),
      'created_at'                      => $this->created_at,
      'updated_at'                      => $this->updated_at,
    ];
  }
}
