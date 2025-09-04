<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Evaluation::class,
      $request,
      Evaluation::filters,
      Evaluation::sorts,
      EvaluationResource::class,
    );
  }

  public function find($id)
  {
    $evaluationCompetence = Evaluation::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store($data)
  {
    $evaluationMetric = Evaluation::create($data);
    return new EvaluationResource($evaluationMetric);
  }

  public function show($id)
  {
    return new EvaluationResource($this->find($id));
  }

  public function update($data)
  {
    $evaluationCompetence = $this->find($data['id']);
    $evaluationCompetence->update($data);
    return new EvaluationResource($evaluationCompetence);
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);
    DB::transaction(function () use ($evaluationCompetence) {
      $evaluationCompetence->delete();
    });
    return response()->json(['message' => 'Evaluación eliminada correctamente']);
  }
}
