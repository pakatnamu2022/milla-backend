<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonResultResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class EvaluationPersonResultService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationPersonResult::class,
      $request,
      EvaluationPersonResult::filters,
      EvaluationPersonResult::sorts,
      EvaluationPersonResultResource::class,
    );
  }

  public function getByPersonAndEvaluation($data)
  {
    $person_id = $data['person_id'];
    $evaluation_id = $data['evaluation_id'];

    $query = EvaluationPersonResult::where('person_id', $person_id)
      ->where('evaluation_id', $evaluation_id);

    $count = $query->count();

    if ($count === 0) {
      throw new Exception('Evaluación de persona no encontrada');
    }
    if ($count > 1) {
      throw new Exception('Se encontraron múltiples evaluaciones para la persona y evaluación especificadas');
    }

    $evaluationPerson = $query->first();
    return EvaluationPersonResultResource::make($evaluationPerson)->showExtra();
  }

  public function find($id)
  {
    $evaluationCompetence = EvaluationPersonResult::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Persona de Evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store($data)
  {
    $evaluationMetric = EvaluationPersonResult::create($data);
    return new EvaluationPersonResultResource($evaluationMetric);
  }

  public function storeMany($evaluationId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);
    $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);
    $categories = EvaluationCycleCategoryDetail::where('cycle_id', $cycle->id)->get();

    EvaluationPersonResult::where('evaluation_id', $evaluation->id)->delete();

    DB::transaction(function () use ($categories, $evaluation) {
      foreach ($categories as $category) {
        $hierarchicalCategory = HierarchicalCategory
          ::where('id', $category->hierarchical_category_id)
          ->with('workers')
          ->first();

        foreach ($hierarchicalCategory->workers as $person) {

          $objectivesPercentage = $category->hasObjectives ? $evaluation->objectivesPercentage : 0;
          $competencesPercentage = $category->hasObjectives ? $evaluation->competencesPercentage : 100;

          $data = [
            'person_id' => $person->id,
            'evaluation_id' => $evaluation->id,
            'objectivesPercentage' => $objectivesPercentage,
            'competencesPercentage' => $competencesPercentage,
            'objectivesResult' => 0,
            'competencesResult' => 0,
            'result' => 0,
          ];
          EvaluationPersonResult::create($data);
        }
      }
    });

    return ['message' => 'Personas de Evaluación creadas correctamente'];
  }

  public function show($id)
  {
    return EvaluationPersonResultResource::make($this->find($id))->showExtra();
  }

  public function update($data)
  {
    $evaluationCompetence = $this->find($data['id']);
    $evaluationCompetence->update($data);
    return new EvaluationPersonResultResource($evaluationCompetence);
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);
    DB::transaction(function () use ($evaluationCompetence) {
      $evaluationCompetence->delete();
    });
    return response()->json(['message' => 'Persona de Evaluación eliminada correctamente']);
  }
}
