<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCompetenceResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCompetence;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationSubCompetence;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationCompetenceService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationCompetence::class,
      $request,
      EvaluationCompetence::filters,
      EvaluationCompetence::sorts,
      EvaluationCompetenceResource::class,
    );
  }

  public function find($id)
  {
    $evaluationCompetence = EvaluationCompetence::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Competencia de evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store(array $data)
  {
    DB::beginTransaction();

    try {
      $competence = EvaluationCompetence::create($data);

      $subCompetences = $data['subCompetences'] ?? [];

      foreach ($subCompetences as $subData) {
        $subCompetence = [
          'competencia_id' => $competence->id,
          'nombre' => $subData['nombre'] ?? null,
          'definicion' => $subData['definicion'] ?? null,
          'level1' => $subData['level1'] ?? null,
          'level2' => $subData['level2'] ?? null,
          'level3' => $subData['level3'] ?? null,
          'level4' => $subData['level4'] ?? null,
          'level5' => $subData['level5'] ?? null,
        ];

        EvaluationSubCompetence::create($subCompetence);
      }

      DB::commit();

      return new EvaluationCompetenceResource($competence);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new EvaluationCompetenceResource($this->find($id));
  }

  public function update($data)
  {
    DB::beginTransaction();

    try {
      $evaluationCompetence = $this->find($data['id']);
      $evaluationCompetence->update($data);

      $subCompetences = $data['subCompetences'] ?? [];

      // IDs que deben permanecer
      $incomingIds = collect($subCompetences)->pluck('id')->filter()->values()->toArray();

      // Eliminar las subcompetencias que ya no están en el payload (efecto dominó)
      $toDelete = EvaluationSubCompetence::where('competencia_id', $evaluationCompetence->id)
        ->whereNotIn('id', $incomingIds)
        ->get();

      foreach ($toDelete as $sub) {
        EvaluationPersonCompetenceDetail::where('sub_competence_id', $sub->id)->delete();
        $sub->delete();
      }

      // Actualizar o crear subcompetencias
      foreach ($subCompetences as $subData) {
        $subFields = [
          'nombre'    => $subData['nombre'] ?? null,
          'definicion' => $subData['definicion'] ?? null,
          'level1'    => $subData['level1'] ?? null,
          'level2'    => $subData['level2'] ?? null,
          'level3'    => $subData['level3'] ?? null,
          'level4'    => $subData['level4'] ?? null,
          'level5'    => $subData['level5'] ?? null,
        ];

        if (isset($subData['id'])) {
          $subCompetence = EvaluationSubCompetence::find($subData['id']);
          if ($subCompetence) {
            $subCompetence->update($subFields);
          }
        } else {
          EvaluationSubCompetence::create(array_merge(
            ['competencia_id' => $evaluationCompetence->id],
            $subFields
          ));
        }
      }

      DB::commit();

      return new EvaluationCompetenceResource($evaluationCompetence);
    } catch (\Throwable $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);

    // Cascade: eliminar person competence details por cada subcompetencia
    $subIds = EvaluationSubCompetence::where('competencia_id', $id)->pluck('id')->toArray();
    if (!empty($subIds)) {
      EvaluationPersonCompetenceDetail::whereIn('sub_competence_id', $subIds)->delete();
    }

    // Cascade: eliminar asignaciones de categoría
    EvaluationCategoryCompetenceDetail::where('competence_id', $id)->delete();

    // Cascade: eliminar subcompetencias
    EvaluationSubCompetence::where('competencia_id', $id)->delete();

    $evaluationCompetence->delete();

    return response()->json(['message' => 'Competencia de evaluación eliminada correctamente']);
  }
}
