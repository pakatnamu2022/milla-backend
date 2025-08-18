<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCycleResource;
use App\Http\Resources\gp\gestionhumana\personal\PersonResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionsistema\Person;
use Exception;
use Illuminate\Http\Request;

class EvaluationCycleService extends BaseService
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            EvaluationCycle::class,
            $request,
            EvaluationCycle::filters,
            EvaluationCycle::sorts,
            EvaluationCycleResource::class,
        );
    }

    public function participants(int $id)
    {
        $cycle = $this->find($id);
        $personsInCycle = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
            ->select('person_id')
            ->distinct()
            ->get()
            ->pluck('person_id')
            ->toArray();
        $persons = Person::whereIn('id', $personsInCycle)->get();
        return WorkerResource::collection($persons);
    }

    public function store($data)
    {
        $evaluationCycle = EvaluationCycle::create($data);
        return new EvaluationCycleResource(EvaluationCycle::find($evaluationCycle->id));
    }

    public function find($id)
    {
        $evaluationCycle = EvaluationCycle::find($id);
        if (!$evaluationCycle) {
            throw new Exception('Ciclo no encontrado');
        }
        return $evaluationCycle;
    }

    public function show($id)
    {
        return new EvaluationCycleResource($this->find($id));
    }

    public function update($data)
    {
        $evaluationCycle = $this->find($data['id']);
        $evaluationCycle->update($data);
        return new EvaluationCycleResource($evaluationCycle);
    }

    public function destroy($id)
    {
        $evaluationCycle = $this->find($id);
        $evaluationCycle->delete();
        return response()->json(['message' => 'Ciclo eliminado correctamente']);
    }
}
