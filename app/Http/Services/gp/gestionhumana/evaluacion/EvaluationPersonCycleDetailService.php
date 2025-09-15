<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailResource;
use App\Http\Resources\gp\gestionhumana\personal\PersonResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
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
      ->where('fecha_inicio', '<=', $lastCycle->cut_off_date) // activos en la fecha de corte
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->whereDoesntHave('evaluationDetails') // sin ningún detail asociado (para evitar incluir personas que ya están en evaluación)
      ->get();

    foreach ($persons as $person) {
      $exists = EvaluationPersonCycleDetail::where('person_id', $person->id)
        ->where('cycle_id', $cycleId)
        ->first();

      if (!$exists) {
        $chief = Person::find($person->jefe_id);
        $objectives = $category->objectives()->get();

        foreach ($objectives as $objective) {
          $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
            ->where('category_id', $categoryId)
            ->where('person_id', $person->id)
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->first();

          if ($categoryObjective) {
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
              'isAscending' => $objective->isAscending,
              'person' => $person->nombre_completo,
              'chief' => $chief ? $chief->nombre_completo : '',
              'position' => $person->position ? $person->position->name : '',
              'sede' => $person->sede ? $person->sede->abreviatura : '',
              'area' => $person->position?->area ? $person->position->area->name : '',
              'category' => $category->name,
              'objective' => $objective->name,
              'goal' => $goal,
              'weight' => $weight,
              'metric' => $objective->metric->name,
              'end_date_objectives' => $lastCycle->end_date_objectives,
            ];
            EvaluationPersonCycleDetail::create($data);
          }
        }
      }
    }
    $evaluationMetric = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
      ->where('category_id', $categoryId)
      ->get();
    return EvaluationPersonCycleDetailResource::collection($evaluationMetric);
  }

  /**
   * Revalida todas las personas de un ciclo completo
   * Verifica que aún cumplan las validaciones originales del store
   */
  public function revalidateAllPersonsInCycle(int $cycleId)
  {
    // 1. Buscar el ciclo
    $cycle = EvaluationCycle::find($cycleId);
    if (!$cycle) {
      throw new Exception('Ciclo no encontrado');
    }

    // 2. Buscar todas las categorías del ciclo
    $categoryDetails = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)
      ->whereNull('deleted_at')
      ->get();

    $results = [];

    foreach ($categoryDetails as $categoryDetail) {
      $categoryId = $categoryDetail->hierarchical_category_id;

      // 3. Buscar todas las personas que YA ESTÁN en esta categoría del ciclo
      $existingPersonDetails = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
        ->where('category_id', $categoryId)
        ->whereNull('deleted_at')
        ->get();

      $category = HierarchicalCategory::find($categoryId);
      if (!$category) continue;

      // 4. Obtener las posiciones válidas para esta categoría
      $validPositions = $category->children()->pluck('position_id')->toArray();

      $revalidatedCount = 0;
      $removedCount = 0;

      foreach ($existingPersonDetails->groupBy('person_id') as $personId => $personDetails) {
        $person = Person::find($personId);

        if (!$person) {
          // Persona no existe: eliminar todos sus detalles
          foreach ($personDetails as $detail) {
            $detail->delete();
            $removedCount++;
          }
          continue;
        }

        // 5. Verificar si la persona aún cumple las validaciones originales
        $stillValid = $this->validatePersonForCycle($person, $cycle, $validPositions);

        if (!$stillValid) {
          // Persona ya no cumple criterios: eliminar todos sus detalles
          foreach ($personDetails as $detail) {
            $detail->delete();
            $removedCount++;
          }
        } else {
          // Persona aún es válida: revalidar sus objetivos
          $this->revalidatePersonObjectives($person, $cycle, $category, $personDetails);
          $revalidatedCount++;
        }
      }

      $results[] = [
        'category_id' => $categoryId,
        'category_name' => $category->name,
        'revalidated_persons' => $revalidatedCount,
        'removed_persons' => $removedCount
      ];
    }

    return [
      'cycle_id' => $cycleId,
      'message' => 'Revalidación completada',
      'results' => $results
    ];
  }

  /**
   * Valida si una persona aún cumple los criterios para estar en el ciclo
   * Aplica las mismas validaciones que en storeByCategoryAndCycle
   */
  private function validatePersonForCycle($person, $cycle, $validPositions)
  {
    // Validación 1: Debe estar en una posición válida para la categoría
    if (!in_array($person->cargo_id, $validPositions)) {
      return false;
    }

    // Validación 2: Fecha de inicio debe ser <= fecha de corte del ciclo
    if ($person->fecha_inicio > $cycle->cut_off_date) {
      return false;
    }

    // Validación 3: Debe estar activa (status_deleted = 1)
    if ($person->status_deleted != 1) {
      return false;
    }

    // Validación 4: Debe tener status_id = 22
    if ($person->status_id != 22) {
      return false;
    }

    return true;
  }

  /**
   * Revalida los objetivos de una persona específica
   */
  private function revalidatePersonObjectives($person, $cycle, $category, $existingDetails)
  {
    // Obtener objetivos actuales de la categoría
    $currentObjectives = $category->objectives()->get();

    foreach ($currentObjectives as $objective) {
      // Verificar si la persona tiene un EvaluationCategoryObjectiveDetail activo para este objetivo
      $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
        ->where('category_id', $category->id)
        ->where('person_id', $person->id)
        ->where('active', 1)
        ->whereNull('deleted_at')
        ->first();

      $existingDetail = $existingDetails->where('objective_id', $objective->id)->first();

      if ($categoryObjective && !$existingDetail) {
        // Objetivo nuevo para esta persona: crear detalle
        $this->createPersonObjectiveDetail($person, $cycle, $category, $objective, $currentObjectives);
      } elseif (!$categoryObjective && $existingDetail) {
        // Objetivo ya no válido: eliminar detalle
        $existingDetail->delete();
      } elseif ($existingDetail) {
        // Objetivo existente: actualizar información básica de la persona por si cambió
        $this->updatePersonBasicInfo($existingDetail, $person, $category);
      }
    }

    // Eliminar detalles de objetivos que ya no existen en la categoría
    $currentObjectiveIds = $currentObjectives->pluck('id')->toArray();
    foreach ($existingDetails as $detail) {
      if (!in_array($detail->objective_id, $currentObjectiveIds)) {
        $detail->delete();
      }
    }
  }

  /**
   * Crea un nuevo detalle de objetivo para una persona
   */
  private function createPersonObjectiveDetail($person, $cycle, $category, $objective, $allObjectives)
  {
    $chief = Person::find($person->jefe_id);

    // Aplicar la misma lógica de goal y weight que en storeByCategoryAndCycle
    $goal = 0;
    $weight = 0;

    // Buscar en ciclo anterior
    $lastCycle = EvaluationCycle::where('id', '<', $cycle->id)->orderBy('id', 'desc')->first();
    if ($lastCycle) {
      $personCycleDetail = EvaluationPersonCycleDetail::where('person_id', $person->id)
        ->where('cycle_id', $lastCycle->id)
        ->where('category_id', $category->id)
        ->where('objective_id', $objective->id)
        ->whereNull('deleted_at')
        ->first();
      $goal = $personCycleDetail ? $personCycleDetail->goal : 0;
      $weight = $personCycleDetail ? $personCycleDetail->weight : 0;
    }

    // Si no hay goal, buscar en EvaluationCategoryObjectiveDetail
    if ($goal === 0) {
      $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
        ->where('category_id', $category->id)
        ->where('person_id', $person->id)
        ->whereNull('deleted_at')
        ->first();
      $goal = $categoryObjective ? $categoryObjective->goal : 0;
      if ($weight === 0) {
        $weight = $categoryObjective ? $categoryObjective->weight : 0;
      }
    }

    // Si aún no hay goal, usar goalReference del objetivo
    if ($goal === 0) {
      $goal = $objective->goalReference;
      if ($weight === 0) {
        $weight = round(100 / $allObjectives->count(), 2);
      }
    }

    $data = [
      'person_id' => $person->id,
      'chief_id' => $person->jefe_id,
      'position_id' => $person->cargo_id,
      'sede_id' => $person->sede_id,
      'area_id' => $person->area_id,
      'cycle_id' => $cycle->id,
      'category_id' => $category->id,
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

  /**
   * Actualiza la información básica de una persona en su detalle
   */
  private function updatePersonBasicInfo($detail, $person, $category)
  {
    $chief = Person::find($person->jefe_id);

    $detail->update([
      'chief_id' => $person->jefe_id,
      'position_id' => $person->cargo_id,
      'sede_id' => $person->sede_id,
      'area_id' => $person->area_id,
      'person' => $person->nombre_completo,
      'chief' => $chief ? $chief->nombre_completo : '',
      'position' => $person->position ? $person->position->name : '',
      'sede' => $person->sede ? $person->sede->abreviatura : '',
      'area' => $person->position?->area ? $person->position->area->name : '',
      'category' => $category->name,
    ]);
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
