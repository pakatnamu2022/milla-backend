<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationParameterResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationParameter;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationParameterService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationParameter::class,
            $request,
            EvaluationParameter::filters,
            EvaluationParameter::sorts,
            EvaluationParameterResource::class,
        );
    }

    public function find($id)
    {
        $evaluationCompetence = EvaluationParameter::where('id', $id)->first();
        if (!$evaluationCompetence) {
            throw new Exception('Parametero no encontrado');
        }
        return $evaluationCompetence;
    }

    public function show($id)
    {
        return new EvaluationParameterResource($this->find($id));
    }

    public function store(array $data)
    {
        $details = $data['details'] ?? [];
        unset($data['details']);
        $data['isPercentage'] = $data['type'] !== 'competences';
        $evaluationMetric = EvaluationParameter::create($data);
        $this->syncDetails($evaluationMetric, $details);
        return new EvaluationParameterResource($evaluationMetric);
    }

    public function update(array $data)
    {
        $details = $data['details'] ?? [];
        unset($data['details']);
        $data['isPercentage'] = $data['type'] !== 'competences';
        $evaluationMetric = $this->find($data['id']);
        $evaluationMetric->update($data);
        $this->syncDetails($evaluationMetric, $details);
        return new EvaluationParameterResource($evaluationMetric);
    }

    private function syncDetails(EvaluationParameter $parameter, array $details): void
    {
        if (empty($details)) return;
        $parameter->details()->delete();
        $parameter->details()->createMany($details);
    }


    public function destroy($id)
    {
        $evaluationCompetence = $this->find($id);
        DB::transaction(function () use ($evaluationCompetence) {
            $evaluationCompetence->delete();
        });
        return response()->json(['message' => 'Parametero eliminado correctamente']);
    }
}
