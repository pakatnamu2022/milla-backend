<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailResource;
use App\Http\Resources\gp\gestionhumana\personal\PersonResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionsistema\Person;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\json;

class EvaluationPersonCycleDetailService extends BaseService
{

  public function __construct(
    protected EvaluationCategoryObjectiveDetailService $categoryObjectiveDetailService
  )
  {
  }


  public function list(Request $request, int $id)
  {
    return $this->getFilteredResults(
      EvaluationPersonCycleDetail::where('cycle_id', $id),
      $request,
      EvaluationPersonCycleDetail::filters,
      EvaluationPersonCycleDetail::sorts,
      EvaluationPersonCycleDetailResource::class,
    );
  }

  public function find($id)
  {
    $personCycleDetail = EvaluationPersonCycleDetail::where('id', $id)->first();
    if (!$personCycleDetail) {
      throw new Exception('Detalle de Ciclo Persona no encontrado');
    }
    return $personCycleDetail;
  }

  public function store(array $data)
  {
    return $this->storeByCategoryAndCycle(
      $data['cycle_id'],
      $data['category_id']
    );
  }

  public function storeByCategoryAndCycle(int $cycleId, int $categoryId)
  {
    $lastCycle = EvaluationCycle::where('id', $cycleId)->orderBy('id', 'desc')->first();
    $category = HierarchicalCategory::find($categoryId);
    $positions = $category->children()->pluck('position_id')->toArray();
    $persons = Person::whereIn('cargo_id', $positions)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->whereDoesntHave('evaluationDetails') // sin ningún detail asociado
      ->get();

    foreach ($persons as $person) {
      $exists = EvaluationPersonCycleDetail::where('person_id', $person->id)
        ->where('cycle_id', $cycleId)
        ->first();

      if (!$exists) {
        $chief = Person::find($person->jefe_id);
        $objectives = $category->objectives()->get();

        foreach ($objectives as $objective) {
          $goal = 0;
          $weight = 0;

          if ($lastCycle) {
            $personCycleDetail = EvaluationPersonCycleDetail::where('person_id', $person->id)
              ->where('cycle_id', $lastCycle->id)
              ->where('category_id', $categoryId)
              ->where('objective_id', $objective->id)
              ->whereNull('deleted_at')
              ->first();
            $goal = $personCycleDetail ? $personCycleDetail->goal : 0;
            $weight = $personCycleDetail ? $personCycleDetail->weight : 0;
          }

          if ($goal === 0) {
            $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
              ->where('category_id', $categoryId)
              ->where('person_id', $person->id)
              ->whereNull('deleted_at')
              ->first();
            $goal = $categoryObjective ? $categoryObjective->goal : 0;
            if ($weight === 0) {
              $weight = $categoryObjective ? $categoryObjective->weight : 0;
            }
          }

          if ($goal === 0) {
            $goal = $objective->goalReference;
            if ($weight === 0) {
              $weight = round(100 / $objectives->count(), 2);
            }
          }

          $data = [
            'person_id' => $person->id,
            'chief_id' => $person->jefe_id,
            'position_id' => $person->cargo_id,
            'sede_id' => $person->sede_id,
            'area_id' => $person->area_id,
            'cycle_id' => $cycleId,
            'category_id' => $categoryId,
            'objective_id' => $objective->id,
            'person' => $person->nombre_completo,
            'chief' => $chief ? $chief->nombre_completo : '',
            'position' => $person->position ? $person->position->name : '',
            'sede' => $person->sede ? $person->sede->abreviatura : '',
            'area' => $person->position?->area ? $person->position->area->name : '',
            'category' => $category->name,
            'objective' => $objective->name,
            'goal' => $goal,
            'weight' => $weight,
          ];
          EvaluationPersonCycleDetail::create($data);
        }
      }
    }
    $evaluationMetric = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
      ->where('category_id', $categoryId)
      ->get();
    return EvaluationPersonCycleDetailResource::collection($evaluationMetric);
  }

  public function show($id)
  {
    return new EvaluationPersonCycleDetailResource($this->find($id));
  }

  public function update($data)
  {
    $personCycleDetail = $this->find($data['id']);
    $data['fixedWeight'] = isset($data['weight']) && $data['weight'] > 0;
    $personCycleDetail->update($data);
    $this->recalculateWeights($personCycleDetail->id);

    DB::transaction(function () use ($personCycleDetail) {
      $categoryObjectiveDetail = EvaluationCategoryObjectiveDetail::where('objective_id', $personCycleDetail->objective_id)
        ->where('category_id', $personCycleDetail->category_id)
        ->where('person_id', $personCycleDetail->person_id)
        ->whereNull('deleted_at')
        ->first();

      if ($categoryObjectiveDetail) {
        $data = [
          'id' => $categoryObjectiveDetail->id,
          'goal' => $personCycleDetail->goal,
        ];

        $this->categoryObjectiveDetailService->update($data);
      }
    });
    return new EvaluationPersonCycleDetailResource($personCycleDetail);
  }

  public function recalculateWeights($cyclePersonDetailId)
  {
    $personCycleDetail = $this->find($cyclePersonDetailId);

    $allObjectives = EvaluationPersonCycleDetail::where('person_id', $personCycleDetail->person_id)
      ->where('chief_id', $personCycleDetail->chief_id)
      ->where('position_id', $personCycleDetail->position_id)
      ->where('sede_id', $personCycleDetail->sede_id)
      ->where('area_id', $personCycleDetail->area_id)
      ->where('cycle_id', $personCycleDetail->cycle_id)
      ->where('category_id', $personCycleDetail->category_id)
      ->get();

    $fixedObjectives = $allObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === true);
    $nonFixedObjectives = $allObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === false);

    $usedWeight = $fixedObjectives->sum('weight');
    $remaining = max(0, 100 - $usedWeight); // evitar negativos


    $count = $nonFixedObjectives->count();
    $weight = $count > 0 ? round($remaining / $count, 2) : 0;

    foreach ($nonFixedObjectives as $objective) {
      $objective->update([
        'weight' => $weight,
        'fixedWeight' => false,
      ]);
    }

    return ['message' => 'Pesos recalculados correctamente'];
  }


  public function destroy($id)
  {
    $personCycleDetail = $this->find($id);
    DB::transaction(function () use ($personCycleDetail) {
      $this->recalculateWeights($personCycleDetail->id);
      $personCycleDetail->delete();
    });
    return response()->json(['message' => 'Detalle de Ciclo Persona eliminado correctamente']);
  }
}
