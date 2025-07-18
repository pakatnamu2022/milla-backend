<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationMetricResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationMetric;
use Exception;
use Illuminate\Http\Request;

class EvaluationMetricService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationMetric::class,
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
        $evaluationMetric = EvaluationMetric::where('id', $id)->first();
        if (!$evaluationMetric) {
            throw new Exception('Métrica no encontrada');
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
        $evaluationMetric->delete();
        return response()->json(['message' => 'Métrica eliminada correctamente']);
    }
}
