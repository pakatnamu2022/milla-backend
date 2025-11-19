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
      'tasks' => $this->tasks ? $this->tasks->map(function ($task) {
        return [
          'id' => $task->id,
          'description' => $task->description,
          'end_date' => $task->end_date ? $task->end_date->format('Y-m-d') : null,
          'fulfilled' => (bool)$task->fulfilled,
        ];
      }) : [],
    ];
  }
}
