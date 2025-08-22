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
    $evaluationMetric = EvaluationCategoryObjectiveDetail::create($data);
    return new EvaluationCategoryObjectiveDetailResource(EvaluationCategoryObjectiveDetail::find($evaluationMetric->id));
  }

  public function find($id)
  {
    $evaluationMetric = EvaluationCategoryObjectiveDetail::where('id', $id)->first();
    if (!$evaluationMetric) {
      throw new Exception('Objetivo de Categoría no encontrado');
    }
    return $evaluationMetric;
  }

  public function show($id)
  {
    return new EvaluationCategoryObjectiveDetailResource($this->find($id));
  }

  public function update($data)
  {
    $evaluationMetric = $this->find($data['id']);
    $evaluationMetric->update($data);
    return new EvaluationCategoryObjectiveDetailResource($evaluationMetric);
  }

  public function destroy($id)
  {
    $evaluationMetric = $this->find($id);
    $evaluationMetric->delete();
    return response()->json(['message' => 'Objetivo de Categoria eliminado correctamente']);
  }
}
