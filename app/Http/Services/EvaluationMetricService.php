<?php

namespace App\Http\Services;

use App\Http\Resources\EvaluationMetricResource;
use App\Models\EvaluationMetric;
use Exception;
use Illuminate\Http\Request;

class EvaluationMetricService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationMetric::where('status_deleted', 0),
            $request,
            EvaluationMetric::filters,
            EvaluationMetric::sorts,
            EvaluationMetricResource::class,
        );
    }

    public function store($data)
    {
        $evaluationMetric = EvaluationMetric::create($data);
        return new EvaluationMetricResource(EvaluationMetric::find($evaluationMetric->id));
    }

    public function find($id)
    {
        $evaluationMetric = EvaluationMetric::where('id', $id)
            ->where('status_deleted', 0)->first();
        if (!$evaluationMetric) {
            throw new Exception('Métrica de evaluación no encontrada');
        }
        return $evaluationMetric;
    }

    public function show($id)
    {
        return new EvaluationMetricResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationMetric = $this->find($data['id']);
        $evaluationMetric->update($data);
        return new EvaluationMetricResource($evaluationMetric);
    }

    public function destroy($id)
    {
        $evaluationMetric = $this->find($id);
        $evaluationMetric->status_deleted = 1; // Mark as deleted
        $evaluationMetric->save();
        return response()->json(['message' => 'Métrica de evaluación eliminada correctamente']);
    }
}
