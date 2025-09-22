<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

  public function assignObjectivesToWorkers()
  {
    DB::beginTransaction();

    try {
      $workersProcessed = [];
      $objectivesAssigned = 0;

      // Buscar workers que tienen categoría pero no tienen objetivos y que requieren objetivos
      $workers = Worker::where('status_id', 22)
        ->with(['position.hierarchicalCategory.objectives', 'objectives', 'evaluationDetails'])
        ->get()
        ->filter(function ($worker) {
          // Excluir si tiene EvaluationPersonDetail
          if ($worker->evaluationDetails->count() > 0) {
            return false;
          }

          $hasCategory = $worker->position && $worker->position->hierarchicalCategory;

          if ($hasCategory) {
            $category = $worker->position->hierarchicalCategory;
            $hasObjectives = $worker->objectives->count() > 0;

            // Solo incluir si la categoría requiere objetivos pero no los tiene
            return $category->hasObjectives && !$hasObjectives;
          }

          return false;
        });

      foreach ($workers as $worker) {
        $category = $worker->position->hierarchicalCategory;
        $objectivesForCategory = $category->objectives; // Esto ya filtra solo los activos por la relación en HierarchicalCategory

        $workerData = [
          'id' => $worker->id,
          'name' => $worker->nombre_completo,
          'position' => $worker->position->name,
          'hierarchical_category' => $category->name,
          'objectives_assigned' => []
        ];

        foreach ($objectivesForCategory as $objective) {
          // Crear el registro en EvaluationCategoryObjectiveDetail
          $objectiveDetail = EvaluationCategoryObjectiveDetail::create([
            'objective_id' => $objective->id,
            'category_id' => $category->id,
            'person_id' => $worker->id,
            'goal' => $objective->goalReference, // Usar la meta de referencia del objetivo
            'weight' => $objective->fixedWeight ?? 1, // Usar el peso fijo o 1 por defecto
            'fixedWeight' => true,
            'active' => true
          ]);

          $workerData['objectives_assigned'][] = [
            'objective_id' => $objective->id,
            'objective_name' => $objective->name,
            'goal_reference' => $objective->goalReference,
            'weight' => $objective->fixedWeight ?? 1
          ];

          $objectivesAssigned++;
        }

        $workersProcessed[] = $workerData;
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => "Se asignaron objetivos exitosamente",
        'summary' => [
          'workers_processed' => count($workersProcessed),
          'objectives_assigned' => $objectivesAssigned
        ],
        'data' => $workersProcessed
      ]);

    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
    }
  }
}
