<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCompetenceResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCompetence;
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

      // Update sub-competences
      $subCompetences = $data['subCompetences'] ?? [];
      foreach ($subCompetences as $subData) {
        if (isset($subData['id'])) {
          // Update existing sub-competence
          $subCompetence = EvaluationSubCompetence::find($subData['id']);
          if ($subCompetence) {
            $subCompetence->update([
              'nombre' => $subData['nombre'] ?? null,
              'definicion' => $subData['definicion'] ?? null,
              'level1' => $subData['level1'] ?? null,
              'level2' => $subData['level2'] ?? null,
              'level3' => $subData['level3'] ?? null,
              'level4' => $subData['level4'] ?? null,
              'level5' => $subData['level5'] ?? null,
            ]);
          }
        } else {
          // Create new sub-competence
          EvaluationSubCompetence::create([
            'competencia_id' => $evaluationCompetence->id,
            'nombre' => $subData['nombre'] ?? null,
            'definicion' => $subData['definicion'] ?? null,
            'level1' => $subData['level1'] ?? null,
            'level2' => $subData['level2'] ?? null,
            'level3' => $subData['level3'] ?? null,
            'level4' => $subData['level4'] ?? null,
            'level5' => $subData['level5'] ?? null,
          ]);
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
    $evaluationCompetence->delete();

    // Also mark related sub-competences as deleted
    EvaluationSubCompetence::where('competencia_id', $id)->delete();

    return response()->json(['message' => 'Competencia de evaluación eliminada correctamente']);
  }
}
