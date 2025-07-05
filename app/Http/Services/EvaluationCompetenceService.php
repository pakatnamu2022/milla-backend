<?php

namespace App\Http\Services;

use App\Http\Resources\EvaluationCompetenceResource;
use App\Models\EvaluationCompetence;
use Exception;
use Illuminate\Http\Request;

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

    public function store($data)
    {
        $evaluationMetric = EvaluationCompetence::create($data);
        return new EvaluationCompetenceResource(EvaluationCompetence::find($evaluationMetric->id));
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

    public function show($id)
    {
        return new EvaluationCompetenceResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationMetric = $this->find($data['id']);
        $evaluationMetric->update($data);
        return new EvaluationCompetenceResource($evaluationMetric);
    }

    public function destroy($id)
    {
        $evaluationMetric = $this->find($id);
        $evaluationMetric->status_deleted = 1; // Mark as deleted
        $evaluationMetric->save();
        return response()->json(['message' => 'Métrica de evaluación eliminada correctamente']);
    }
}
