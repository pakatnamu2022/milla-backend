<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
  protected bool $showExtra = false;

  public function showExtra($show = true): static
  {
    $this->showExtra = $show;
    return $this;
  }

  public function toArray(Request $request): array
  {
    $response = [
      'id' => $this->id,
      'name' => $this->name,
      'start_date' => $this->start_date,
      'end_date' => $this->end_date,
      'typeEvaluation' => $this->typeEvaluation,
      'typeEvaluationName' => config('evaluation.typesEvaluation')[$this->typeEvaluation] ?? null,
      'objectivesPercentage' => $this->objectivesPercentage,
      'competencesPercentage' => $this->competencesPercentage,
      'cycle_id' => $this->cycle_id,
      'period_id' => $this->period_id,
      'competence_parameter_id' => $this->competence_parameter_id,
      'objective_parameter_id' => $this->objective_parameter_id,
      'final_parameter_id' => $this->final_parameter_id,
      'status' => $this->status,
      'statusName' => config('evaluation.statusEvaluation')[$this->status] ?? config('evaluation.statusEvaluation.0'),
      'period' => $this->period?->name,
      'cycle' => $this->cycle?->name,
      'competenceParameter' => $this->competenceParameter?->name,
      'objectiveParameter' => $this->objectiveParameter?->name,
      'finalParameter' => $this->finalParameter?->name,
      'send_opened_email' => $this->send_opened_email,
      'send_closed_email' => $this->send_closed_email,
    ];

    if ($this->showExtra) {
      $response['progress_stats'] = $this->progress_stats;
      $response['results_stats'] = $this->results_stats;
    }

    return $response;
  }
}
