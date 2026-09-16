<?php

namespace App\Http\Resources\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\Interview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                     => $this->id,
      'proceso_postulacion_id' => $this->proceso_postulacion_id,
      'proceso'                => $this->whenLoaded('process', fn() => $this->process?->nombre_postulacion),
      'persona_id'             => $this->persona_id,
      'postulante'             => $this->whenLoaded('applicant', fn() => $this->applicant?->nombre_completo),
      'fase'                   => $this->fase,
      'fase_label'             => Interview::FASE_LABELS[(int) $this->fase] ?? (string) $this->fase,
      'entrevistador_id'       => $this->entrevistador_id,
      'entrevistador'          => $this->whenLoaded('interviewer', fn() => $this->interviewer?->name),
      'fecha_entrevista'       => $this->fecha_entrevista?->format('Y-m-d H:i'),
      'resultado_promedio'     => $this->resultado_promedio,
      'observaciones'          => $this->observaciones,
      'scores'                 => InterviewScoreResource::collection($this->whenLoaded('scores')),
      'created_at'             => $this->created_at,
      'updated_at'             => $this->updated_at,
    ];
  }
}
