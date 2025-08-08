<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionsistema\Person;
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
        $this->storeByCategoryAndCycle(
            $data['cycle_id'],
            $data['category_id']
        );
    }

    public function storeByCategoryAndCycle(int $cycleId, int $categoryId)
    {
        $category = HierarchicalCategory::find($categoryId);
        $positions = $category->children()->pluck('position_id')->toArray();
        $persons = Person::whereIn('cargo_id', $positions)->where('status_id', 22)->get();
//        dd($persons);

        foreach ($persons as $person) {
            $exists = EvaluationPersonCycleDetail::where('person_id', $person->id)
                ->where('cycle_id', $cycleId)
                ->first();
            if (!$exists) {

                $chief = Person::find($person->jefe_id);

                $data = [
                    'person_id' => $person->id,
                    'chief_id' => $person->jefe_id,
                    'position_id' => $person->cargo_id,
                    'sede_id' => $person->sede_id,
                    'area_id' => $person->area_id,
                    'cycle_id' => $cycleId,
                    'category_id' => $categoryId,
//                    'objective_id' =>
                    'person' => $person->nombre_completo,
                    'chief' => $chief ? $chief->nombre_completo : '',
                    'position' => $person->position ? $person->position->name : '',
                    'sede' => $person->sede ? $person->sede->abreviatura : '',
                    'area' => $person->sede?->area ? $person->sede->area->name : '',
                    'category' => $category->name,
//                    'objective' => '',

                ];
                $this->createDetail($data);
            }
        }

        $detail = [
            'person_id' => '',
            'chief_id' => '',
            'position_id' => '',
            'sede_id' => '',
            'area_id' => '',
            'cycle_id' => '',
            'category_id' => '',
            'objective_id' => '',
            'person' => '',
            'chief' => '',
            'position' => '',
            'sede' => '',
            'area' => '',
            'category' => '',
            'objective' => '',
            'goal' => '',
            'weight' => '',
        ];


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
