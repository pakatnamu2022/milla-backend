<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Http\Request;

class WorkerService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Worker::class,
      $request,
      Worker::filters,
      Person::sorts,
      WorkerResource::class,
    );
  }

  public function getWorkersWithoutCategoriesAndObjectives()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'objectives', 'competences', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Si tiene EvaluationPersonDetail, debe ser excluido
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Si no tiene categoría jerárquica
        if (!$hasCategory) {
          $worker->inclusion_reason = 'No tiene categoría jerárquica';
          $worker->has_category = false;
          $worker->has_objectives = false;
          $worker->has_competences = false;
          return true;
        }

        $category = $worker->position->hierarchicalCategory;
        $hasObjectives = $worker->objectives->count() > 0;
        $hasCompetences = $worker->competences->count() > 0;

        $worker->has_category = true;
        $worker->has_objectives = $hasObjectives;
        $worker->has_competences = $hasCompetences;

        // Si tiene categoría pero no tiene competencias
        if (!$hasCompetences) {
          $worker->inclusion_reason = 'No tiene competencias';
          return true;
        }

        // Si la categoría requiere objetivos (hasObjectives = true) pero no los tiene
        if ($category->hasObjectives && !$hasObjectives) {
          $worker->inclusion_reason = 'No tiene objetivos';
          return true;
        }

        // Si la categoría no requiere objetivos (hasObjectives = false) es normal, no incluir
        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutObjectives()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'objectives', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Excluir si tiene EvaluationPersonDetail
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Solo incluir si tiene categoría que requiere objetivos pero no los tiene
        if ($hasCategory) {
          $category = $worker->position->hierarchicalCategory;
          $hasObjectives = $worker->objectives->count() > 0;

          if ($category->hasObjectives && !$hasObjectives) {
            $worker->inclusion_reason = 'No tiene objetivos';
            $worker->has_category = true;
            $worker->has_objectives = false;
            return true;
          }
        }

        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutCategories()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Excluir si tiene EvaluationPersonDetail
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Solo incluir si NO tiene categoría jerárquica
        if (!$hasCategory) {
          $worker->inclusion_reason = 'No tiene categoría jerárquica';
          $worker->has_category = false;
          return true;
        }

        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }

  public function getWorkersWithoutCompetences()
  {
    $workers = Worker::where('status_id', 22)
      ->with(['position.hierarchicalCategory', 'competences', 'evaluationDetails'])
      ->get()
      ->filter(function ($worker) {
        // Excluir si tiene EvaluationPersonDetail
        if ($worker->evaluationDetails->count() > 0) {
          return false;
        }

        $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

        // Solo incluir si tiene categoría pero no tiene competencias
        if ($hasCategory) {
          $hasCompetences = $worker->competences->count() > 0;

          if (!$hasCompetences) {
            $worker->inclusion_reason = 'No tiene competencias';
            $worker->has_category = true;
            $worker->has_competences = false;
            return true;
          }
        }

        return false;
      })
      ->values();

    return WorkerResource::collection($workers);
  }
}
