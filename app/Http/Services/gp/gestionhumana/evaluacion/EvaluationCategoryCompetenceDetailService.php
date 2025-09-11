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
}
