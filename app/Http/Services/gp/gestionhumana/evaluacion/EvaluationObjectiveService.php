<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationObjectiveResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function response;

class EvaluationObjectiveService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationObjective::class,
            $request,
            EvaluationObjective::filters,
            EvaluationObjective::sorts,
            EvaluationObjectiveResource::class,
        );
    }

    public function find($id)
    {
        $evaluationCompetence = EvaluationObjective::where('id', $id)->first();
        if (!$evaluationCompetence) {
            throw new Exception('Objetivo no encontrado');
        }
        return $evaluationCompetence;
    }

    public function store(array $data)
    {
        $evaluationMetric = EvaluationObjective::create($data);
        return new EvaluationObjectiveResource($evaluationMetric);
    }

    public function show($id)
    {
        return new EvaluationObjectiveResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationCompetence = $this->find($data['id']);
        $evaluationCompetence->update($data);
        return new EvaluationObjectiveResource($evaluationCompetence);
    }

    public function destroy($id)
    {
        $evaluationCompetence = $this->find($id);
        DB::transaction(function () use ($evaluationCompetence) {
            $evaluationCompetence->delete();
        });
        return response()->json(['message' => 'Objetivo eliminado correctamente']);
    }
}
