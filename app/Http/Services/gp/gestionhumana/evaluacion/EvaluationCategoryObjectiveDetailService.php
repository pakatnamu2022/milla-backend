<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationCategoryObjectiveDetailService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationCategoryObjectiveDetail::class,
      $request,
      EvaluationCategoryObjectiveDetail::filters,
      EvaluationCategoryObjectiveDetail::sorts,
      EvaluationCategoryObjectiveDetailResource::class,
    );
  }


  public function workers(int $id)
  {
    $hierarchicalCategory = HierarchicalCategory::findOrFail($id);
    $workers = $hierarchicalCategory->workers()->get();

//  Agrupar objetivos por person_id y dentro de la persona que este los objetivos
    return $workers->map(function ($worker) use ($id) {
      $objectives = EvaluationCategoryObjectiveDetail::where('category_id', $id)
        ->where('person_id', $worker->id)
        ->whereHas('objective', function ($query) {
          $query->where('active', true);
        })
        ->whereNull('deleted_at')
        ->get();
      return [
        'worker' => new WorkerResource($worker),
        'objectives' => EvaluationCategoryObjectiveDetailResource::collection($objectives),
      ];
    });
  }

  public function recalculateWeights($categoryId, $personId)
  {
    $allObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
      ->where('person_id', $personId)
      ->where('active', 1)
      ->whereNull('deleted_at');

    $activeObjectives = (clone $allObjectives)->where('active', true)->get();
    $inactiveObjectives = (clone $allObjectives)->where('active', false)->get();

    $fixedObjectives = $activeObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === true);
    $nonFixedObjectives = $activeObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === false);

    $usedWeight = $fixedObjectives->sum('weight');
    $remaining = max(0, 100 - $usedWeight); // evitar negativos


    $count = $nonFixedObjectives->count();
    $weight = $count > 0 ? round($remaining / $count, 2) : 0;

    foreach ($nonFixedObjectives as $objective) {
      $objective->update([
        'weight' => $weight,
        'fixedWeight' => false,
      ]);
    }

    foreach ($inactiveObjectives as $objective) {
      $objective->update([
        'weight' => 0,
        'fixedWeight' => false,
      ]);
    }

    return ['message' => 'Pesos recalculados correctamente'];
  }

  public function store($data)
  {
    $category = HierarchicalCategory::findOrFail($data['category_id']);
    $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();
    $objective = EvaluationObjective::findOrFail($data['objective_id']);

    foreach ($workers as $workerId) {
      EvaluationCategoryObjectiveDetail::create([
        'objective_id' => $objective->id,
        'category_id' => $data['category_id'],
        'person_id' => $workerId,
        'goal' => $objective->goalReference,
        'fixedWeight' => $objective->fixedWeight,
        'weight' => $objective->fixedWeight ? $objective->weight : 0,
      ]);
      $this->recalculateWeights($category->id, $workerId);
    }

    return EvaluationCategoryObjectiveDetailResource::collection(
      EvaluationCategoryObjectiveDetail::where('category_id', $data['category_id'])->get()
    );
  }

  public function assignMissingObjectives()
  {
    $categories = HierarchicalCategory::with(['workers', 'objectives'])->get();

    foreach ($categories as $category) {
      $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();
      $objectives = $category->objectives()->pluck('gh_evaluation_objective.id')->toArray();

      foreach ($workers as $workerId) {
        // Verificar el estado actual del trabajador
        $existingObjectivesCount = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
          ->where('person_id', $workerId)
          ->whereHas('objective', function ($query) {
            $query->where('active', true);
          })
          ->count();

        $hasActiveObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
          ->where('person_id', $workerId)
          ->where('active', 1)
          ->exists();

        // Determinar si es un trabajador completamente nuevo
        $isCompletelyNew = $existingObjectivesCount === 0;

        foreach ($objectives as $objectiveId) {
          $exists = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
            ->where('person_id', $workerId)
            ->where('objective_id', $objectiveId)
            ->exists();

          if (!$exists) {
            $objective = EvaluationObjective::find($objectiveId);

            // Lógica refinada:
            // - Si es completamente nuevo (0 objetivos) → nuevos van activos
            // - Si ya existía en el sistema (tiene algunos objetivos) → nuevos van inactivos
            $activeStatus = $isCompletelyNew ? 1 : 0;

            EvaluationCategoryObjectiveDetail::create([
              'objective_id' => $objectiveId,
              'category_id' => $category->id,
              'person_id' => $workerId,
              'goal' => $objective->goalReference,
              'fixedWeight' => false,
              'weight' => 0,
              'active' => $activeStatus,
            ]);
          }
        }
        $this->recalculateWeights($category->id, $workerId);
      }
    }
  }

  public function regeneratePersonObjectives(int $categoryId, int $personId)
  {
    $category = HierarchicalCategory::findOrFail($categoryId);
    $objectives = $category->objectives()->pluck('gh_evaluation_objective.id')->toArray();

    foreach ($objectives as $objectiveId) {
      $exists = EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
        ->where('person_id', $personId)
        ->where('objective_id', $objectiveId)
        ->exists();

      if (!$exists) {
        $objective = EvaluationObjective::findOrFail($objectiveId);
        EvaluationCategoryObjectiveDetail::create([
          'objective_id' => $objectiveId,
          'category_id' => $categoryId,
          'person_id' => $personId,
          'goal' => $objective->goalReference,
          'fixedWeight' => false,
          'weight' => 0,
          'active' => 1,
        ]);
      }
    }

    $this->recalculateWeights($categoryId, $personId);

    return EvaluationCategoryObjectiveDetailResource::collection(
      EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
        ->where('person_id', $personId)
        ->whereNull('deleted_at')
        ->get()
    );
  }

  public function recalculateHomogeneousWeights(int $categoryId, int $personId)
  {
    EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
      ->where('person_id', $personId)
      ->whereNull('deleted_at')
      ->update(['fixedWeight' => false, 'weight' => 0]);

    $this->recalculateWeights($categoryId, $personId);

    return EvaluationCategoryObjectiveDetailResource::collection(
      EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
        ->where('person_id', $personId)
        ->whereNull('deleted_at')
        ->get()
    );
  }

  public function find($id)
  {
    $categoryObjective = EvaluationCategoryObjectiveDetail::where('id', $id)->first();
    if (!$categoryObjective) {
      throw new Exception('Objetivo de Categoría no encontrado');
    }
    return $categoryObjective;
  }

  public function show($id)
  {
    return new EvaluationCategoryObjectiveDetailResource($this->find($id));
  }

  public function update($data)
  {
    $categoryObjective = $this->find($data['id']);
    if (isset($data['weight'])) {
      $data['fixedWeight'] = $data['weight'] > 0 ? true : false;
    }
    $categoryObjective->update($data);
    $this->recalculateWeights($categoryObjective->category_id, $categoryObjective->person_id);
    $categoryObjective = $this->find($data['id']);

    DB::transaction(function () use ($categoryObjective) {
      $objective = EvaluationObjective::find($categoryObjective->objective_id);
      $objective->update([
        'fixedWeight' => $categoryObjective->fixedWeight,
        'weight' => $categoryObjective->fixedWeight ? $categoryObjective->weight : 0,
        'goalReference' => $categoryObjective->goal,
      ]);
    });

    return new EvaluationCategoryObjectiveDetailResource($categoryObjective);
  }

  public function destroy($data)
  {
    $categoryId = $data['category_id'];
    $objectiveId = $data['objective_id'];
    $workers = HierarchicalCategory::find($categoryId)->workers()->pluck('rrhh_persona.id')->toArray();
    EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
      ->where('objective_id', $objectiveId)
      ->whereNull('deleted_at')->delete();
    foreach ($workers as $workerId) {
      $this->recalculateWeights($categoryId, $workerId);
    }
    return response()->json(['message' => 'Objetivo de Categoria eliminado correctamente']);
  }

  public function weightValidationReport(int $categoryId): array
  {
    $category = HierarchicalCategory::findOrFail($categoryId);
    $workers = $category->workers()->get();

    $referenceObjectives = $category->objectives()->get()->map(fn($o) => [
      'objective_id'   => $o->id,
      'objective_name' => $o->name,
      'goal_reference' => $o->goalReference,
      'weight'         => $o->weight,
      'fixed_weight'   => (bool) $o->fixedWeight,
    ])->values();

    $report = $workers->map(function ($worker) use ($categoryId) {
      $activeObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
        ->where('person_id', $worker->id)
        ->where('active', 1)
        ->whereNull('deleted_at')
        ->whereHas('objective', fn($q) => $q->where('active', 1))
        ->with('objective')
        ->get();

      $hasObjectives = $activeObjectives->isNotEmpty();
      $hasZeroWeights = $activeObjectives->contains(fn($o) => $o->weight == 0);
      $totalWeight = $activeObjectives->sum('weight');
      $weightSumValid = (int) round($totalWeight) === 100;
      $valid = $hasObjectives && !$hasZeroWeights && $weightSumValid;

      return [
        'person_id' => $worker->id,
        'name' => $worker->nombre_completo ?? '',
        'valid' => $valid,
        'total_weight' => $totalWeight,
        'objectives' => $activeObjectives->map(fn($o) => [
          'objective_id' => $o->objective_id,
          'objective_name' => $o->objective->name ?? '',
          'weight' => $o->weight,
          'valid' => $o->weight > 0,
        ])->values(),
        'issues' => array_values(array_filter([
          !$hasObjectives ? 'Sin objetivos activos' : null,
          $hasZeroWeights ? 'Hay objetivos con peso 0' : null,
          $hasObjectives && !$weightSumValid ? "Suma de pesos = {$totalWeight} (debe ser 100)" : null,
        ])),
      ];
    });

    $validCount = $report->where('valid', true)->count();
    $invalidCount = $report->where('valid', false)->count();

    return [
      'category_id'       => $categoryId,
      'category_name'     => $category->name,
      'total_workers'     => $report->count(),
      'valid_workers'     => $validCount,
      'invalid_workers'   => $invalidCount,
      'is_valid'          => $invalidCount === 0,
      'objectives'        => $referenceObjectives,
      'workers'           => $report->values(),
    ];
  }

  public function globalWeightValidationReport(): array
  {
    $categories = HierarchicalCategory::where('hasObjectives', true)
      ->where('excluded_from_evaluation', false)
      ->get();

    return $categories->map(function ($category) {
      $workers = $category->workers()->get();
      $invalidCount = 0;
      $totalCount = $workers->count();

      foreach ($workers as $worker) {
        $activeObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
          ->where('person_id', $worker->id)
          ->where('active', 1)
          ->whereNull('deleted_at')
          ->whereHas('objective', fn($q) => $q->where('active', 1))
          ->get();

        $valid = $activeObjectives->isNotEmpty()
          && !$activeObjectives->contains(fn($o) => $o->weight == 0)
          && (int) round($activeObjectives->sum('weight')) === 100;

        if (!$valid) $invalidCount++;
      }

      return [
        'category_id' => $category->id,
        'category_name' => $category->name,
        'total_workers' => $totalCount,
        'valid_workers' => $totalCount - $invalidCount,
        'invalid_workers' => $invalidCount,
        'is_valid' => $invalidCount === 0,
      ];
    })->values()->all();
  }

  public function applyReferenceWeightsToAllWorkers(int $categoryId, array $objectives): array
  {
    $activeObjectives = array_filter($objectives, fn($obj) => ($obj['active'] ?? true) !== false);
    $totalWeight = array_sum(array_column($activeObjectives, 'weight'));
    if ((int) round($totalWeight) !== 100) {
      throw new Exception("La suma de pesos debe ser 100. Suma actual: {$totalWeight}");
    }

    $category = HierarchicalCategory::findOrFail($categoryId);
    $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();

    DB::transaction(function () use ($categoryId, $workers, $objectives) {
      foreach ($workers as $workerId) {
        foreach ($objectives as $obj) {
          $isActive = ($obj['active'] ?? true) !== false;
          EvaluationCategoryObjectiveDetail::updateOrCreate(
            [
              'category_id' => $categoryId,
              'person_id' => $workerId,
              'objective_id' => $obj['objective_id'],
            ],
            [
              'weight' => $isActive ? ($obj['weight'] ?? 0) : 0,
              'goal' => $obj['goal'] ?? null,
              'fixedWeight' => $isActive && ($obj['weight'] ?? 0) > 0,
              'active' => $isActive ? 1 : 0,
            ]
          );
        }
      }
    });

    return $this->weightValidationReport($categoryId);
  }
}
