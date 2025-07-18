<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPeriodResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPeriodService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationPeriod::class,
            $request,
            EvaluationPeriod::filters,
            EvaluationPeriod::sorts,
            EvaluationPeriodResource::class,
        );
    }

    public function find($id)
    {
        $evaluationCompetence = EvaluationPeriod::where('id', $id)->first();
        if (!$evaluationCompetence) {
            throw new Exception('Periodo no encontrado');
        }
        return $evaluationCompetence;
    }

    public function store(array $data)
    {
        $evaluationMetric = EvaluationPeriod::create($data);
        return new EvaluationPeriodResource($evaluationMetric);
    }

    public function show($id)
    {
        return new EvaluationPeriodResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationCompetence = $this->find($data['id']);
        $evaluationCompetence->update($data);
        return new EvaluationPeriodResource($evaluationCompetence);
    }

    public function destroy($id)
    {
        $evaluationCompetence = $this->find($id);
        DB::transaction(function () use ($evaluationCompetence) {
            $evaluationCompetence->delete();
        });
        return response()->json(['message' => 'Periodo eliminado correctamente']);
    }
}
