<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationObjectiveResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function response;

class EvaluationObjectiveService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationObjective::class,
      $request,
      EvaluationObjective::filters,
      EvaluationObjective::sorts,
      EvaluationObjectiveResource::class,
    );
  }

  public function find($id)
  {
    $objective = EvaluationObjective::where('id', $id)->first();
    if (!$objective) {
      throw new Exception('Objetivo no encontrado');
    }
    return $objective;
  }

  public function store(array $data)
  {
    $evaluationMetric = EvaluationObjective::create($data);
    return new EvaluationObjectiveResource($evaluationMetric);
  }

  public function show($id)
  {
    return new EvaluationObjectiveResource($this->find($id));
  }

  /**
   * @throws \Throwable
   */
  public function update($data): EvaluationObjectiveResource
  {
    $objective = $this->find($data['id']);
    $objective->update($data);

    /**
     * Update objectives in evaluation_person_cycle_detail of the cycle
     * of the active evaluations that have this objective
     */
    DB::transaction(function () use ($objective) {
      EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
        ->whereNull('deleted_at')
        ->update(['goal' => $objective->goalReference]);

      $evaluationPersonService = new EvaluationPersonService();
      $evaluation = Evaluation::where('status', 1)->first();
      if (!$evaluation) return;

      $cycle = $evaluation->cycle;
      $personCycleDetails = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
        ->where('objective_id', $objective->id)
        ->get();

      $affectedPersonIds = [];

      foreach ($personCycleDetails as $detail) {
        $detail->objective = $objective->name;
        $detail->isAscending = $objective->isAscending;
        if ($objective->metric) {
          $detail->metric = $objective->metric->name;
        }
        $detail->save();
        $affectedPersonIds[$detail->person_id] = true;
      }

      foreach (array_keys($affectedPersonIds) as $personId) {
        $evaluationPersonService->recalculatePersonResults($evaluation->id, $personId);
      }
    });

    return new EvaluationObjectiveResource($objective);
  }

  public function destroy($id)
  {
    $objective = $this->find($id);
    DB::transaction(function () use ($objective) {
      $objective->delete();
    });
    return response()->json(['message' => 'Objetivo eliminado correctamente']);
  }

  public function activateInCategories(int $objectiveId, ?array $categoryIds = null): array
  {
    $objective = $this->find($objectiveId);
    $detailService = new EvaluationCategoryObjectiveDetailService();
    $affectedCategories = 0;
    $affectedWorkers = 0;

    DB::transaction(function () use ($objective, $detailService, $categoryIds, &$affectedCategories, &$affectedWorkers) {
      $categories = $this->getRelatedActiveCategories($objective->id, $categoryIds);

      foreach ($categories as $category) {
        $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();
        $categoryAffected = false;

        foreach ($workers as $workerId) {
          $updated = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
            ->where('person_id', $workerId)
            ->where('objective_id', $objective->id)
            ->where('active', 0)
            ->whereNull('deleted_at')
            ->update(['active' => 1]);

          if ($updated > 0) {
            $affectedWorkers++;
            $categoryAffected = true;
          }

          $detailService->recalculateWeights($category->id, $workerId);
        }

        if ($categoryAffected) {
          $affectedCategories++;
        }
      }
    });

    return [
      'message' => 'Objetivo activado correctamente en categorías relacionadas',
      'affected_categories' => $affectedCategories,
      'affected_workers' => $affectedWorkers,
    ];
  }

  public function deactivateInCategories(int $objectiveId, ?array $categoryIds = null): array
  {
    $objective = $this->find($objectiveId);
    $detailService = new EvaluationCategoryObjectiveDetailService();
    $affectedCategories = 0;
    $affectedWorkers = 0;

    DB::transaction(function () use ($objective, $detailService, $categoryIds, &$affectedCategories, &$affectedWorkers) {
      $categories = $this->getRelatedActiveCategories($objective->id, $categoryIds);

      foreach ($categories as $category) {
        $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();
        $categoryAffected = false;

        foreach ($workers as $workerId) {
          $updated = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
            ->where('person_id', $workerId)
            ->where('objective_id', $objective->id)
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->update(['active' => 0]);

          if ($updated > 0) {
            $affectedWorkers++;
            $categoryAffected = true;
          }

          $detailService->recalculateWeights($category->id, $workerId);
        }

        if ($categoryAffected) {
          $affectedCategories++;
        }
      }
    });

    return [
      'message' => 'Objetivo desactivado correctamente en categorías relacionadas',
      'affected_categories' => $affectedCategories,
      'affected_workers' => $affectedWorkers,
    ];
  }

  public function previewActivateInCategories(int $objectiveId): array
  {
    $objective = $this->find($objectiveId);
    $categories = $this->getRelatedActiveCategories($objective->id);
    $preview = [];

    foreach ($categories as $category) {
      $workers = $category->workers()->get();
      $workerPreviews = [];

      foreach ($workers as $worker) {
        $currentObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
          ->where('person_id', $worker->id)
          ->whereNull('deleted_at')
          ->get();

        $targetDetail = $currentObjectives->firstWhere('objective_id', $objective->id);
        if (!$targetDetail || $targetDetail->active) {
          continue;
        }

        $workerPreviews[] = [
          'worker_id' => $worker->id,
          'worker_name' => $worker->nombre_completo,
          'current_weights' => $currentObjectives->map(fn($o) => [
            'objective_id' => $o->objective_id,
            'active' => (bool)$o->active,
            'weight' => (float)$o->weight,
            'fixedWeight' => (bool)$o->fixedWeight,
          ])->values(),
          'projected_weights' => $this->simulateActivation($currentObjectives, $objective->id),
        ];
      }

      if (!empty($workerPreviews)) {
        $preview[] = [
          'category_id' => $category->id,
          'category_name' => $category->name,
          'affected_workers_count' => count($workerPreviews),
          'workers' => $workerPreviews,
        ];
      }
    }

    return [
      'objective' => new EvaluationObjectiveResource($objective),
      'affected_categories_count' => count($preview),
      'categories' => $preview,
    ];
  }

  private function getRelatedActiveCategories(int $objectiveId, ?array $filterCategoryIds = null)
  {
    $categoryIds = EvaluationCategoryObjectiveDetail::where('objective_id', $objectiveId)
      ->whereNull('deleted_at')
      ->distinct()
      ->pluck('category_id');

    $query = HierarchicalCategory::whereIn('id', $categoryIds)
      ->where('hasObjectives', true)
      ->where('excluded_from_evaluation', false)
      ->whereNull('deleted_at');

    if (!empty($filterCategoryIds)) {
      $query->whereIn('id', $filterCategoryIds);
    }

    return $query->get();
  }

  public function previewDeactivateInCategories(int $objectiveId): array
  {
    $objective = $this->find($objectiveId);
    $categories = $this->getRelatedActiveCategories($objective->id);
    $preview = [];

    foreach ($categories as $category) {
      $workers = $category->workers()->get();
      $workerPreviews = [];

      foreach ($workers as $worker) {
        $currentObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
          ->where('person_id', $worker->id)
          ->whereNull('deleted_at')
          ->get();

        $targetDetail = $currentObjectives->firstWhere('objective_id', $objective->id);
        if (!$targetDetail || !$targetDetail->active) {
          continue;
        }

        $workerPreviews[] = [
          'worker_id' => $worker->id,
          'worker_name' => $worker->nombre_completo,
          'current_weights' => $currentObjectives->map(fn($o) => [
            'objective_id' => $o->objective_id,
            'active' => (bool)$o->active,
            'weight' => (float)$o->weight,
            'fixedWeight' => (bool)$o->fixedWeight,
          ])->values(),
          'projected_weights' => $this->simulateDeactivation($currentObjectives, $objective->id),
        ];
      }

      if (!empty($workerPreviews)) {
        $preview[] = [
          'category_id' => $category->id,
          'category_name' => $category->name,
          'affected_workers_count' => count($workerPreviews),
          'workers' => $workerPreviews,
        ];
      }
    }

    return [
      'objective' => new EvaluationObjectiveResource($objective),
      'affected_categories_count' => count($preview),
      'categories' => $preview,
    ];
  }

  private function simulateDeactivation($objectives, int $deactivatingObjectiveId): array
  {
    $simulated = $objectives->map(fn($obj) => (object)[
      'objective_id' => $obj->objective_id,
      'active' => $obj->objective_id == $deactivatingObjectiveId ? false : (bool)$obj->active,
      'weight' => $obj->weight,
      'fixedWeight' => (bool)$obj->fixedWeight,
    ]);

    $activeObjs = $simulated->filter(fn($o) => $o->active);
    $fixedObjs = $activeObjs->filter(fn($o) => $o->fixedWeight);
    $nonFixedObjs = $activeObjs->filter(fn($o) => !$o->fixedWeight);

    $usedWeight = $fixedObjs->sum('weight');
    $remaining = max(0, 100 - $usedWeight);
    $count = $nonFixedObjs->count();
    $newWeight = $count > 0 ? round($remaining / $count, 2) : 0;

    return $simulated->map(fn($obj) => [
      'objective_id' => $obj->objective_id,
      'active' => $obj->active,
      'weight' => $obj->active ? ($obj->fixedWeight ? (float)$obj->weight : $newWeight) : 0.0,
      'fixedWeight' => $obj->fixedWeight,
    ])->values()->toArray();
  }

  private function simulateActivation($objectives, int $activatingObjectiveId): array
  {
    $simulated = $objectives->map(fn($obj) => (object)[
      'objective_id' => $obj->objective_id,
      'active' => $obj->objective_id == $activatingObjectiveId ? true : (bool)$obj->active,
      'weight' => $obj->weight,
      'fixedWeight' => (bool)$obj->fixedWeight,
    ]);

    $activeObjs = $simulated->filter(fn($o) => $o->active);
    $fixedObjs = $activeObjs->filter(fn($o) => $o->fixedWeight);
    $nonFixedObjs = $activeObjs->filter(fn($o) => !$o->fixedWeight);

    $usedWeight = $fixedObjs->sum('weight');
    $remaining = max(0, 100 - $usedWeight);
    $count = $nonFixedObjs->count();
    $newWeight = $count > 0 ? round($remaining / $count, 2) : 0;

    return $simulated->map(fn($obj) => [
      'objective_id' => $obj->objective_id,
      'active' => $obj->active,
      'weight' => $obj->active ? ($obj->fixedWeight ? (float)$obj->weight : $newWeight) : 0.0,
      'fixedWeight' => $obj->fixedWeight,
    ])->values()->toArray();
  }
}
