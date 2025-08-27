<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
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

  public function recalculateWeights($categoryId)
  {
    $allObjectives = EvaluationCategoryObjectiveDetail::where('category_id', $categoryId)->get();

    $fixedObjectives = $allObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === true);
    $nonFixedObjectives = $allObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === false);

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

    return ['message' => 'Pesos recalculados correctamente'];
  }

  public function store($data)
  {
    $categoryObjective = EvaluationCategoryObjectiveDetail::create($data);
    $this->recalculateWeights($categoryObjective->category_id);
    return new EvaluationCategoryObjectiveDetailResource($categoryObjective);
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
    $this->recalculateWeights($categoryObjective->category_id);
    $objective = EvaluationObjective::find($categoryObjective->objective_id);
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
