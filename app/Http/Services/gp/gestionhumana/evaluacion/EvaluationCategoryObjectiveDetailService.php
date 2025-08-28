<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;
use function max;
use function round;

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

//    agrupar objetivos por person_id y dentro de la persona que este los objetivos
    $workersWithObjectives = $workers->map(function ($worker) use ($id) {
      $objectives = EvaluationCategoryObjectiveDetail::where('category_id', $id)
        ->where('person_id', $worker->id)
        ->whereNull('deleted_at')
        ->get();
      return [
        'worker' => $worker->nombre_completo,
        'objectives' => EvaluationCategoryObjectiveDetailResource::collection($objectives),
      ];
    });

    return $workersWithObjectives;
  }

  public function recalculateWeights($categoryId, $personId)
  {
    $allObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)
      ->where('person_id', $personId)
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

    foreach ($workers as $workerId) {
      EvaluationCategoryObjectiveDetail::create([
        'objective_id' => $data['objective_id'],
        'category_id' => $data['category_id'],
        'person_id' => $workerId,
      ]);
      $this->recalculateWeights($category->id, $workerId);
    }

    return EvaluationCategoryObjectiveDetailResource::collection(
      EvaluationCategoryObjectiveDetail::where('category_id', $data['category_id'])->get()
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
//    $objective = EvaluationObjective::find($categoryObjective->objective_id);
//    $objective->update([
//      'weight' => $categoryObjective->weight
//      ''
//    ]);
    return new EvaluationCategoryObjectiveDetailResource($categoryObjective);
  }

  public function destroy($id)
  {
    $categoryObjective = $this->find($id);
    $categoryId = $categoryObjective->category_id;
    $categoryObjective->delete();
    $this->recalculateWeights($categoryId);
    return response()->json(['message' => 'Objetivo de Categoria eliminado correctamente']);
  }
}
