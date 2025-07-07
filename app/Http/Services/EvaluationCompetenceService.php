<?php

namespace App\Http\Services;

use App\DTO\ServiceResponse;
use App\Http\Resources\EvaluationCompetenceResource;
use App\Models\EvaluationCompetence;
use App\Models\EvaluationSubCompetence;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationCompetenceService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationCompetence::where('status_delete', 0),
            $request,
            EvaluationCompetence::filters,
            EvaluationCompetence::sorts,
            EvaluationCompetenceResource::class,
        );
    }

    public function store(array $data): ServiceResponse
    {
        DB::beginTransaction();

        try {
            $data['status_delete'] = 0; // Ensure status_delete is set to 0 for new records
            $data['grupo_cargos_id'] = 1; // Ensure status_delete is set to 0 for new records

            $competence = EvaluationCompetence::create($data);

            $subCompetences = $data['subCompetences'] ?? [];

            foreach ($subCompetences as $subData) {
                $subCompetence = [
                    'competencia_id' => $competence->id,
                    'nombre' => $subData['nombre'] ?? null,
                    'definicion' => $subData['definicion'] ?? null,
                    'status_delete' => 0,
                    'level1' => $subData['level1'] ?? null,
                    'level2' => $subData['level2'] ?? null,
                    'level3' => $subData['level3'] ?? null,
                    'level4' => $subData['level4'] ?? null,
                    'level5' => $subData['level5'] ?? null,
                ];

                EvaluationSubCompetence::create($subCompetence);
            }

            DB::commit();

            return ServiceResponse::success(new EvaluationCompetenceResource($competence));
        } catch (\Throwable $e) {
            DB::rollBack();

            return ServiceResponse::error('Error al crear: ' . $e->getMessage());
        }
    }


    public function find($id)
    {
        $evaluationMetric = EvaluationCompetence::where('id', $id)
            ->where('status_delete', 0)->first();
        if (!$evaluationMetric) {
            throw new Exception('Compentencia no encontrada');
        }
        return $evaluationMetric;
    }

    public function show($id): ServiceResponse
    {
        try {
            $evaluationMetric = $this->find($id);
            return ServiceResponse::success(new EvaluationCompetenceResource($evaluationMetric));
        } catch (Exception $e) {
            return ServiceResponse::error($e->getMessage());
        }
    }

    public function update($data)
    {
        try {
            $evaluationMetric = $this->find($data['id']);

            $evaluationMetric->update($data);

            // Update subcompetences if provided
            if (isset($data['subCompetences'])) {
                foreach ($data['subCompetences'] as $subData) {
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
                }
            }

            return ServiceResponse::success(new EvaluationCompetenceResource($evaluationMetric));
        } catch (Exception $e) {
            return ServiceResponse::error('Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $evaluationMetric = $this->find($id);
        $evaluationMetric->status_deleted = 1; // Mark as deleted
        $evaluationMetric->save();
        return response()->json(['message' => 'Métrica de evaluación eliminada correctamente']);
    }
}
