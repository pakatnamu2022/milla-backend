<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetailResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCompetence;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;

class EvaluationCategoryCompetenceDetailService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationCategoryCompetenceDetail::class,
      $request,
      EvaluationCategoryCompetenceDetail::filters,
      EvaluationCategoryCompetenceDetail::sorts,
      EvaluationCategoryCompetenceDetailResource::class,
    );
  }


  public function workers(int $id)
  {
    $hierarchicalCategory = HierarchicalCategory::findOrFail($id);
    $workers = $hierarchicalCategory->workers()->get();

    $workersWithcompetences = $workers->map(function ($worker) use ($id) {
      $competences = EvaluationCategoryCompetenceDetail::where('category_id', $id)
        ->where('person_id', $worker->id)
        ->whereNull('deleted_at')
        ->get();
      return [
        'worker' => new WorkerResource($worker),
        'competences' => EvaluationCategoryCompetenceDetailResource::collection($competences),
      ];
    });

    return $workersWithcompetences;
  }

  public function store($data)
  {
    $category = HierarchicalCategory::findOrFail($data['category_id']);
    $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();
    $competence = EvaluationCompetence::findOrFail($data['competence_id']);

    foreach ($workers as $workerId) {
      EvaluationCategoryCompetenceDetail::firstOrCreate([
        'competence_id' => $competence->id,
        'category_id' => $data['category_id'],
        'person_id' => $workerId,
      ]);
    }

    return EvaluationCategoryCompetenceDetailResource::collection(
      EvaluationCategoryCompetenceDetail::where('category_id', $data['category_id'])->get()
    );
  }


  public function assignCompetencesToWorkers()
  {
    $categories = HierarchicalCategory::with(['workers', 'objectives'])->get();
    foreach ($categories as $category) {
      $workers = $category->workers;
      $competences = $category->competences;
      foreach ($workers as $worker) {
        foreach ($competences as $competence) {
          $exists = EvaluationCategoryCompetenceDetail::where('category_id', $category->id)
            ->where('person_id', $worker->id)
            ->where('competence_id', $competence->id)
            ->whereNull('deleted_at')
            ->first();
          if (!$exists) {
            EvaluationCategoryCompetenceDetail::create([
              'competence_id' => $competence->id,
              'category_id' => $category->id,
              'person_id' => $worker->id,
            ]);
          }
        }
      }
    }
    return ['message' => 'Competencias asignadas a los trabajadores correctamente'];
  }

  public function find($id)
  {
    $categoryCompetence = EvaluationCategoryCompetenceDetail::where('id', $id)->first();
    if (!$categoryCompetence) {
      throw new Exception('Competencia de Categoría no encontrado');
    }
    return $categoryCompetence;
  }

  public function show($id)
  {
    return new EvaluationCategoryCompetenceDetailResource($this->find($id));
  }

  public function update($data)
  {
    $categoryCompetence = $this->find($data['id']);
    if (isset($data['weight'])) {
      $data['fixedWeight'] = $data['weight'] > 0 ? true : false;
    }
    $categoryCompetence->update($data);
    $categoryCompetence = $this->find($data['id']);
    return new EvaluationCategoryCompetenceDetailResource($categoryCompetence);
  }

  public function destroy($data)
  {
    $categoryId = $data['category_id'];
    $competenceId = $data['competence_id'];
    EvaluationCategoryCompetenceDetail::where('category_id', $categoryId)
      ->where('competence_id', $competenceId)
      ->whereNull('deleted_at')->delete();
    return response()->json(['message' => 'Competencia de Categoria eliminado correctamente']);
  }

  public function assignmentReport(int $categoryId): array
  {
    $category = HierarchicalCategory::findOrFail($categoryId);
    $workers = $category->workers()->get();

    $referenceCompetences = $category->competences()->get()->map(fn($c) => [
      'competence_id'   => $c->id,
      'competence_name' => $c->nombre,
    ])->values();

    $report = $workers->map(function ($worker) use ($categoryId, $referenceCompetences) {
      $assigned = EvaluationCategoryCompetenceDetail::where('category_id', $categoryId)
        ->where('person_id', $worker->id)
        ->whereNull('deleted_at')
        ->pluck('competence_id')
        ->toArray();

      $missing = $referenceCompetences->filter(fn($c) => !in_array($c['competence_id'], $assigned));
      $valid = $missing->isEmpty() && $referenceCompetences->isNotEmpty();

      return [
        'person_id'           => $worker->id,
        'name'                => $worker->nombre_completo ?? '',
        'valid'               => $valid,
        'total_competences'   => $referenceCompetences->count(),
        'assigned_competences'=> count($assigned),
        'missing_competences' => $missing->values(),
        'issues'              => array_values(array_filter([
          $referenceCompetences->isEmpty() ? 'La categoría no tiene competencias configuradas' : null,
          $missing->isNotEmpty() ? 'Faltan ' . $missing->count() . ' competencia(s) por asignar' : null,
        ])),
      ];
    });

    $validCount   = $report->where('valid', true)->count();
    $invalidCount = $report->where('valid', false)->count();

    return [
      'category_id'      => $categoryId,
      'category_name'    => $category->name,
      'total_workers'    => $report->count(),
      'valid_workers'    => $validCount,
      'invalid_workers'  => $invalidCount,
      'is_valid'         => $invalidCount === 0,
      'competences'      => $referenceCompetences,
      'workers'          => $report->values(),
    ];
  }

  public function globalAssignmentReport(): array
  {
    $categories = HierarchicalCategory::where('excluded_from_evaluation', false)->get();

    return $categories->map(function ($category) {
      $workers = $category->workers()->get();
      $competenceIds = $category->competences()->pluck('gh_config_competencias.id')->toArray();
      $totalCount   = $workers->count();
      $invalidCount = 0;

      foreach ($workers as $worker) {
        $assignedIds = EvaluationCategoryCompetenceDetail::where('category_id', $category->id)
          ->where('person_id', $worker->id)
          ->whereNull('deleted_at')
          ->pluck('competence_id')
          ->toArray();

        $allAssigned = empty(array_diff($competenceIds, $assignedIds));
        $valid = !empty($competenceIds) && $allAssigned;

        if (!$valid) $invalidCount++;
      }

      return [
        'category_id'      => $category->id,
        'category_name'    => $category->name,
        'total_workers'    => $totalCount,
        'valid_workers'    => $totalCount - $invalidCount,
        'invalid_workers'  => $invalidCount,
        'is_valid'         => $invalidCount === 0,
      ];
    })->values()->all();
  }

  public function regeneratePersonCompetences(int $categoryId, int $personId)
  {
    $category = HierarchicalCategory::findOrFail($categoryId);
    $competences = $category->competences()->pluck('gh_config_competencias.id')->toArray();

    foreach ($competences as $competenceId) {
      EvaluationCategoryCompetenceDetail::firstOrCreate([
        'competence_id' => $competenceId,
        'category_id'   => $categoryId,
        'person_id'     => $personId,
      ]);
    }

    return EvaluationCategoryCompetenceDetailResource::collection(
      EvaluationCategoryCompetenceDetail::where('category_id', $categoryId)
        ->where('person_id', $personId)
        ->whereNull('deleted_at')
        ->get()
    );
  }

  public function fillAllMissingCompetences(): array
  {
    $categories = HierarchicalCategory::where('excluded_from_evaluation', false)
      ->with('competences')
      ->get();

    $filled = 0;
    $categoriesProcessed = 0;

    foreach ($categories as $category) {
      $competenceIds = $category->competences->pluck('id')->toArray();
      if (empty($competenceIds)) continue;

      $workers = $category->workers()->pluck('rrhh_persona.id')->toArray();
      $categoriesProcessed++;

      foreach ($workers as $workerId) {
        foreach ($competenceIds as $competenceId) {
          [, $created] = EvaluationCategoryCompetenceDetail::firstOrCreate([
            'competence_id' => $competenceId,
            'category_id'   => $category->id,
            'person_id'     => $workerId,
          ]);
          if ($created) $filled++;
        }
      }
    }

    return [
      'message'              => 'Competencias faltantes asignadas correctamente',
      'categories_processed' => $categoriesProcessed,
      'assignments_created'  => $filled,
    ];
  }
}
