<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use Exception;
use Illuminate\Http\Request;

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

  public function store($data)
  {
    $categoryObjective = EvaluationCategoryObjectiveDetail::create($data);
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
    $categoryObjective->update($data);
    return new EvaluationCategoryObjectiveDetailResource($categoryObjective);
  }

  public function destroy($id)
  {
    $categoryObjective = $this->find($id);
    $categoryObjective->delete();
    return response()->json(['message' => 'Objetivo de Categoria eliminado correctamente']);
  }
}
