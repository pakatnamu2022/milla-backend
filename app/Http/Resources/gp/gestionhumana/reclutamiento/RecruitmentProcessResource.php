<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentProcessResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                 => $this->id,
      'nombre_postulacion' => $this->nombre_postulacion,
      'cant_trab_solicita' => $this->cant_trab_solicita,
      'sede_id'            => $this->sede_id,
      'sede'               => $this->whenLoaded('sede', fn() => $this->sede?->abreviatura ?? $this->sede?->name),
      'area_id'            => $this->area_id,
      'area'              => $this->whenLoaded('area', fn() => $this->area?->name),
      'cargo_id'           => $this->cargo_id,
      'cargo'              => $this->whenLoaded('position', fn() => $this->position?->name),
      'centro_costo_id'    => $this->centro_costo_id,
      'fecha_inicio'       => $this->fecha_inicio?->format('Y-m-d'),
      'fecha_fin_plazo'    => $this->fecha_fin_plazo?->format('Y-m-d'),
      'fecha_fin_cierre'   => $this->fecha_fin_cierre?->format('Y-m-d'),
      'dias_plazo'         => $this->dias_plazo,
      'status_id'          => $this->status_id,
      'status'             => $this->whenLoaded('status', fn() => [
        'id'     => $this->status?->id,
        'estado' => $this->status?->estado,
        'color'  => $this->status?->color,
      ]),
      'is_open'            => $this->status_id !== RecruitmentProcess::STATUS_CLOSED,
      'applicants_count'   => $this->whenCounted('applicants'),
      'created_at'         => $this->created_at,
      'updated_at'         => $this->updated_at,
    ];
  }
}
