<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonCycleDetailService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationPersonCycleDetail::class,
            $request,
            EvaluationPersonCycleDetail::filters,
            EvaluationPersonCycleDetail::sorts,
            EvaluationPersonCycleDetailResource::class,
        );
    }

    public function find($id)
    {
        $evaluationCompetence = EvaluationPersonCycleDetail::where('id', $id)->first();
        if (!$evaluationCompetence) {
            throw new Exception('Detalle de Ciclo Persona no encontrado');
        }
        return $evaluationCompetence;
    }

    public function store(array $data)
    {
        $evaluationMetric = EvaluationPersonCycleDetail::create($data);
        return new EvaluationPersonCycleDetailResource($evaluationMetric);
    }

    public function show($id)
    {
        return new EvaluationPersonCycleDetailResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationCompetence = $this->find($data['id']);
        $evaluationCompetence->update($data);
        return new EvaluationPersonCycleDetailResource($evaluationCompetence);
    }

    public function destroy($id)
    {
        $evaluationCompetence = $this->find($id);
        DB::transaction(function () use ($evaluationCompetence) {
            $evaluationCompetence->delete();
        });
        return response()->json(['message' => 'Detalle de Ciclo Persona eliminado correctamente']);
    }
}
