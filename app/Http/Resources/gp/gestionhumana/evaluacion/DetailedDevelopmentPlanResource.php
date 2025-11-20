<?php

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailedDevelopmentPlanResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'description' => $this->description,
      'boss_confirms' => (bool)$this->boss_confirms,
      'worker_confirms' => (bool)$this->worker_confirms,
      'boss_confirms_completion' => (bool)$this->boss_confirms_completion,
      'worker_confirms_completion' => (bool)$this->worker_confirms_completion,
      'worker_id' => $this->worker_id,
      'worker_name' => $this->worker ? $this->worker->nombre_completo : null,
      'boss_id' => $this->boss_id,
      'boss_name' => $this->boss ? $this->boss->nombre_completo : null,
      'evaluation_name' => $this->evaluation ? $this->evaluation->name : null,
      'comment' => $this->comment,
      'tasks' => $this->tasks ? $this->tasks->map(function ($task) {
        return [
          'id' => $task->id,
          'description' => $task->description,
          'end_date' => $task->end_date ? $task->end_date->format('Y-m-d') : null,
          'fulfilled' => (bool)$task->fulfilled,
        ];
      }) : [],
      'objectives_competences' => $this->objectivesCompetences ? $this->objectivesCompetences->map(function ($objComp) {
        return [
          'objective_detail' => $objComp->objectiveDetail ? [
            'id' => $objComp->objectiveDetail->id,
            'objective' => $objComp->objectiveDetail->objective,
            // Agrega aquí los campos que necesites del objetivo
          ] : null,
          'competence_detail' => $objComp->competenceDetail ? [
            'id' => $objComp->competenceDetail->id,
            'competence' => $objComp->competenceDetail->competence,
            // Agrega aquí los campos que necesites de la competencia
          ] : null,
        ];
      }) : [],
    ];
  }
}
