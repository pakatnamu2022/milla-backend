<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluationPersonCycleDetailService extends BaseService
{
  protected EvaluationCategoryObjectiveDetailService $categoryObjectiveDetailService;
  protected EvaluationPersonService $evaluationPersonService;

  public function __construct()
  {
    $this->categoryObjectiveDetailService = new EvaluationCategoryObjectiveDetailService();
    $this->evaluationPersonService = new EvaluationPersonService();
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
    // 0. PRIMERO: Limpiar registros huérfanos del ciclo completo antes de crear nuevos
    // Esto asegura que no haya EvaluationPersonResult/CompetenceDetail apuntando a detalles eliminados
    $this->cleanupOrphanedEvaluationRecords($cycleId);

    $lastCycle = EvaluationCycle::where('id', $cycleId)->orderBy('id', 'desc')->first();
    $category = HierarchicalCategory::find($categoryId);
    $positions = $category->children()->pluck('position_id')->toArray();
    $persons = Worker::whereIn('cargo_id', $positions)
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
        $evaluatorId = $person->supervisor_id ?? $person->jefe_id;
        $chief = Worker::find($evaluatorId);
        $objectives = $category->objectives()->get();

        foreach ($objectives as $objective) {
          $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
            ->whereHas('objective', function ($query) use ($objective) {
              $query->where('active', 1);
            })
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
                ->whereHas('objective', function ($query) use ($objective) {
                  $query->where('active', 1);
                })
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
              'chief_id' => $evaluatorId ?? throw new Exception('La persona ' . $person->nombre_completo . ' de la categoría ' . $category->name . ' no tiene un evaluador asignado.'),
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
              'metric' => $objective->metric->name ?? throw new Exception('El objetivo ' . $objective->name . ' no tiene una métrica asignada.'),
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
   * Regenera los personCycleDetail para una persona específica en un ciclo
   * Elimina los existentes y los vuelve a crear con datos actualizados
   *
   * @param int $cycleId - ID del ciclo
   * @param int $personId - ID de la persona
   * @return \Illuminate\Support\Collection - Colección de personCycleDetails regenerados
   * @throws Exception
   */
  public function regenerateForPerson(int $cycleId, int $personId)
  {
    return DB::transaction(function () use ($cycleId, $personId) {
      // 0. PRIMERO: Limpiar todos los registros huérfanos de esta persona en este ciclo
      // Esto incluye EvaluationPersonResult y EvaluationPersonCompetenceDetail que puedan
      // estar apuntando a EvaluationPersonCycleDetail ya eliminados
      $evaluationIds = Evaluation::where('cycle_id', $cycleId)->pluck('id');
      foreach ($evaluationIds as $evaluationId) {
        EvaluationPersonResult::where('evaluation_id', $evaluationId)
          ->where('person_id', $personId)
          ->delete();
        EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
          ->where('person_id', $personId)
          ->delete();
      }

      // 1. Buscar el ciclo y validar que existe
      $cycle = EvaluationCycle::find($cycleId);
      if (!$cycle) {
        throw new Exception('Ciclo no encontrado');
      }

      // 2. Buscar la persona (Worker) y validar que existe
      $person = Worker::find($personId);
      if (!$person) {
        throw new Exception('Persona no encontrada');
      }

      // 3. Obtener la categoría jerárquica de la persona
      $hierarchicalCategory = $person->position?->hierarchicalCategory;
      if (!$hierarchicalCategory) {
        throw new Exception('La persona ' . $person->nombre_completo . ' no tiene una categoría jerárquica asignada.');
      }

      // 4. Validar que la persona tiene evaluador (supervisor_id o jefe_id)
      $evaluatorId = $person->supervisor_id ?? $person->jefe_id;
      if (!$evaluatorId) {
        throw new Exception('La persona ' . $person->nombre_completo . ' de la categoría ' . $hierarchicalCategory->name . ' no tiene un evaluador asignado.');
      }

      $chief = Worker::find($evaluatorId);
      if (!$chief) {
        throw new Exception('No se encontró el evaluador con ID ' . $evaluatorId . ' para la persona ' . $person->nombre_completo);
      }

      // 5. Obtener los personCycleDetail existentes de esa persona en ese ciclo (incluyendo eliminados)
      $existingDetails = EvaluationPersonCycleDetail::where('person_id', $personId)
        ->where('cycle_id', $cycleId)
        ->get();

      // 5.1. Eliminar los personCycleDetail existentes (si quedan algunos activos)
      foreach ($existingDetails as $detail) {
        $detail->delete();
      }

      // 6. Obtener los objetivos de la categoría jerárquica
      $objectives = $hierarchicalCategory->objectives()->get();
      if ($objectives->isEmpty()) {
        throw new Exception('La categoría jerárquica ' . $hierarchicalCategory->name . ' no tiene objetivos asignados.');
      }

      $regeneratedDetails = collect();

      // 7. Para cada objetivo, crear el personCycleDetail
      foreach ($objectives as $objective) {
        // Verificar si la persona tiene un EvaluationCategoryObjectiveDetail activo para este objetivo
        $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
          ->whereHas('objective', function ($query) use ($objective) {
            $query->where('active', 1);
          })
          ->where('category_id', $hierarchicalCategory->id)
          ->where('person_id', $person->id)
          ->where('active', 1)
          ->whereNull('deleted_at')
          ->first();

        if ($categoryObjective) {
          $goal = 0;
          $weight = 0;

          // Intentar obtener goal y weight del ciclo anterior
          $previousCycle = EvaluationCycle::where('id', '<', $cycle->id)
            ->orderBy('id', 'desc')
            ->first();

          if ($previousCycle) {
            $previousDetail = EvaluationPersonCycleDetail::withTrashed()
              ->where('person_id', $person->id)
              ->where('cycle_id', $previousCycle->id)
              ->where('category_id', $hierarchicalCategory->id)
              ->where('objective_id', $objective->id)
              ->whereNull('deleted_at')
              ->first();

            if ($previousDetail) {
              $goal = $previousDetail->goal;
              $weight = $previousDetail->weight;
            }
          }

          // Si no hay goal del ciclo anterior, usar el de categoryObjective
          if ($goal === 0) {
            $goal = $categoryObjective->goal ?? 0;
            if ($weight === 0) {
              $weight = $categoryObjective->weight ?? 0;
            }
          }

          // Si aún no hay goal, usar goalReference del objetivo
          if ($goal === 0) {
            $goal = $objective->goalReference;
            if ($weight === 0) {
              $weight = round(100 / $objectives->count(), 2);
            }
          }

          $data = [
            'person_id' => $person->id,
            'chief_id' => $evaluatorId,
            'position_id' => $person->cargo_id,
            'sede_id' => $person->sede_id,
            'area_id' => $person->area_id,
            'cycle_id' => $cycle->id,
            'category_id' => $hierarchicalCategory->id,
            'objective_id' => $objective->id,
            'isAscending' => $objective->isAscending,
            'person' => $person->nombre_completo,
            'chief' => $chief->nombre_completo,
            'position' => $person->position?->name ?? '',
            'sede' => $person->sede?->abreviatura ?? '',
            'area' => $person->position?->area?->name ?? '',
            'category' => $hierarchicalCategory->name,
            'objective' => $objective->name,
            'goal' => $goal,
            'weight' => $weight,
            'metric' => $objective->metric?->name ?? '',
            'end_date_objectives' => $cycle->end_date_objectives,
          ];

          $detail = EvaluationPersonCycleDetail::create($data);
          $regeneratedDetails->push($detail);
        }
      }

      // 8. Retornar la colección de detalles regenerados
      return $regeneratedDetails;
    });
  }

  /**
   * Revalida todas las personas de un ciclo completo
   * Verifica que aún cumplan las validaciones originales del store
   */
  public function revalidateAllPersonsInCycle(int $cycleId)
  {
    try {
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
          $person = Worker::find($personId);

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
            // Persona ya no cumple criterios: eliminar todos sus detalles y evaluaciones asociadas
            // 1. Limpiar evaluaciones asociadas antes de eliminar
            $this->cleanupAssociatedEvaluationsForPerson($personId, $personDetails);

            // 2. Ahora sí eliminar los EvaluationPersonCycleDetail (los EvaluationPerson se eliminan por CASCADE)
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

      // Después de todas las validaciones, limpiar registros huérfanos que puedan haber quedado
      $orphanedCount = $this->cleanupOrphanedEvaluationRecords($cycleId);

      return [
        'cycle_id' => $cycleId,
        'message' => 'Revalidación completada',
        'results' => $results,
        'orphaned_records_cleaned' => $orphanedCount
      ];
    } catch (Exception $e) {
      Log::error('Error en revalidación de personas en ciclo: ' . $e->getMessage(), ['cycle_id' => $cycleId]);
      return [
        'cycle_id' => $cycleId,
        'message' => 'Error durante la revalidación: ' . $e->getMessage(),
        'results' => []
      ];
    }
  }

  /**
   * Limpia todos los registros huérfanos de evaluaciones en un ciclo
   * Encuentra EvaluationPerson que apuntan a EvaluationPersonCycleDetail eliminados
   * y los actualiza para que apunten a los nuevos activos (preservando evaluaciones existentes)
   *
   * @param int $cycleId
   * @return int Cantidad de registros actualizados/limpiados
   */
  private function cleanupOrphanedEvaluationRecords(int $cycleId)
  {
    $cleanedCount = 0;

    // 1. Obtener todas las evaluaciones de este ciclo
    $evaluationIds = Evaluation::where('cycle_id', $cycleId)->pluck('id');

    foreach ($evaluationIds as $evaluationId) {
      // 2. Encontrar EvaluationPerson que apunten a EvaluationPersonCycleDetail eliminados
      $orphanedEvalPersons = DB::table('gh_evaluation_person as ep')
        ->leftJoin('gh_evaluation_person_cycle_detail as pcd', 'ep.person_cycle_detail_id', '=', 'pcd.id')
        ->where('ep.evaluation_id', $evaluationId)
        ->where(function ($query) {
          $query->whereNotNull('pcd.deleted_at')  // detail está eliminado
          ->orWhereNull('pcd.id');           // detail no existe
        })
        ->whereNull('ep.deleted_at')  // pero EvaluationPerson está activo
        ->select('ep.id', 'ep.person_id', 'ep.person_cycle_detail_id', 'pcd.person_id as pcd_person_id', 'pcd.cycle_id', 'pcd.objective_id')
        ->get();

      foreach ($orphanedEvalPersons as $orphaned) {
        // 3. Obtener el detail eliminado para saber qué buscar
        $oldDetail = DB::table('gh_evaluation_person_cycle_detail')
          ->where('id', $orphaned->person_cycle_detail_id)
          ->first();

        // Si el detail no existe en absoluto, no podemos mapear -> eliminar
        if (!$oldDetail) {
          EvaluationPerson::where('id', $orphaned->id)->delete();
          $cleanedCount++;
          continue;
        }

        // 4. Buscar el EvaluationPersonCycleDetail ACTIVO correspondiente
        // Emparejamos por person_id, cycle_id y objective_id del detail eliminado
        $newDetail = EvaluationPersonCycleDetail::where('person_id', $oldDetail->person_id)
          ->where('cycle_id', $oldDetail->cycle_id)
          ->where('objective_id', $oldDetail->objective_id)
          ->whereNull('deleted_at')
          ->first();

        if ($newDetail) {
          // OPCIÓN 1: Actualizar la referencia (preserva la evaluación existente)
          EvaluationPerson::where('id', $orphaned->id)
            ->update(['person_cycle_detail_id' => $newDetail->id]);
          $cleanedCount++;
        } else {
          // OPCIÓN 2: Si no existe un detail activo correspondiente, eliminar el huérfano
          // (esto significa que el objetivo fue removido completamente de la persona)
          EvaluationPerson::where('id', $orphaned->id)->delete();
          $cleanedCount++;
        }
      }

      // 3. Obtener todos los person_id únicos en EvaluationPersonResult de esta evaluación
      $personIdsInResults = EvaluationPersonResult::where('evaluation_id', $evaluationId)
        ->pluck('person_id')
        ->unique();

      foreach ($personIdsInResults as $personId) {
        // 4. Verificar si existe un EvaluationPersonCycleDetail activo para esta persona en este ciclo
        $hasActiveDetail = EvaluationPersonCycleDetail::where('person_id', $personId)
          ->where('cycle_id', $cycleId)
          ->whereNull('deleted_at')
          ->exists();

        // 5. Si no existe, eliminar los registros huérfanos
        if (!$hasActiveDetail) {
          EvaluationPersonResult::where('evaluation_id', $evaluationId)
            ->where('person_id', $personId)
            ->delete();
          $cleanedCount++;
        }
      }

      // 6. Hacer lo mismo para EvaluationPersonCompetenceDetail
      $personIdsInCompetence = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
        ->pluck('person_id')
        ->unique();

      foreach ($personIdsInCompetence as $personId) {
        $hasActiveDetail = EvaluationPersonCycleDetail::where('person_id', $personId)
          ->where('cycle_id', $cycleId)
          ->whereNull('deleted_at')
          ->exists();

        if (!$hasActiveDetail) {
          EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
            ->where('person_id', $personId)
            ->delete();
          $cleanedCount++;
        }
      }
    }

    return $cleanedCount;
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

    // Validacion 5: Si el typeEvaluation del ciclo es 0, debe tener objetivos la categoria de la persona
    if ($cycle->typeEvaluation == Evaluation::EVALUATION_TYPE_OBJECTIVES) {
      $hierarchicalCategory = $person->position?->hierarchicalCategory;
      if (!$hierarchicalCategory || $hierarchicalCategory->objectives()->count() == 0) {
        return false;
      }
    }

    // Validación 6: si el typeEvaluacion del ciclo es diferente de 0, debe tener objetivos y competencias
    if ($cycle->typeEvaluation != Evaluation::EVALUATION_TYPE_OBJECTIVES) {
      $hierarchicalCategory = $person->position?->hierarchicalCategory;
      if (
        !$hierarchicalCategory ||
        $hierarchicalCategory->objectives()->count() == 0 ||
        $hierarchicalCategory->competencies()->count() == 0
      ) {
        return false;
      }
    }

    // Validación 7: Debe tener un evaluador asignado (supervisor_id o jefe_id)
    if (!$person->supervisor_id) {
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
        ->whereHas('objective', function ($query) {
          $query->where('active', true);
        })
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
        // Objetivo ya no válido: limpiar evaluaciones asociadas y eliminar detalle
        $this->cleanupAssociatedEvaluations($existingDetail);
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
        $this->cleanupAssociatedEvaluations($detail);
        $detail->delete();
      }
    }
  }

  /**
   * Crea un nuevo detalle de objetivo para una persona
   */
  private function createPersonObjectiveDetail($person, $cycle, $category, $objective, $allObjectives)
  {
    $chief = Worker::find($person->supervisor_id ?? $person->jefe_id);

    if (!$chief) {
      throw new Exception('La persona ' . $person->nombre_completo . ' de la categoría ' . $category->name . ' no tiene un evaluador asignado.');
    }

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
        ->whereHas('objective', function ($query) {
          $query->where('active', true);
        })
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
      'chief_id' => $person->supervisor_id ?? $person->jefe_id ?? throw new Exception('La persona ' . $person->nombre_completo . ' de la categoría ' . $category->name . ' no tiene un evaluador asignado.'),
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
      'metric' => $objective->metric->name ?? throw new Exception('El objetivo ' . $objective->name . ' no tiene una métrica asignada.'),
      'end_date_objectives' => $cycle->end_date_objectives,
      'isAscending' => $objective->isAscending,
    ];

    EvaluationPersonCycleDetail::create($data);
  }

  /**
   * Actualiza la información básica de una persona en su detalle
   */
  private function updatePersonBasicInfo($detail, $person, $category)
  {
    $chief = Worker::find($person->supervisor_id ?? $person->jefe_id);
    if (!$chief) {
      throw new Exception('La persona ' . $person->nombre_completo . ' de la categoría ' . $category->name . ' no tiene un evaluador asignado.');
    }

    $detail->update([
      'chief_id' => $person->supervisor_id,
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
    if (isset($data['weight'])) $this->recalculateWeights($personCycleDetail->id);

    DB::transaction(function () use ($personCycleDetail) {
      // Actualizar EvaluationCategoryObjectiveDetail
      $categoryObjectiveDetail = EvaluationCategoryObjectiveDetail::where('objective_id', $personCycleDetail->objective_id)
        ->whereHas('objective', function ($query) {
          $query->where('active', true);
        })
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

      // Actualizar EvaluationPerson relacionados (solo en evaluaciones activas o en progreso)
      $evaluationPersons = EvaluationPerson::where('person_cycle_detail_id', $personCycleDetail->id)
        ->whereNull('deleted_at')
        ->get();

      foreach ($evaluationPersons as $evaluationPerson) {
        $updateData = [
          'id' => $evaluationPerson->id,
          'person_id' => $personCycleDetail->person_id,
          'chief_id' => $personCycleDetail->chief_id,
          'chief' => $personCycleDetail->chief,
        ];

        // Si tiene result, agregarlo para que el servicio recalcule compliance y qualification
        if ($evaluationPerson->result > 0) {
          $updateData['result'] = $evaluationPerson->result;
        }

        // Usar el servicio de EvaluationPerson que ya tiene la lógica de cálculo
        $this->evaluationPersonService->update($updateData);
      }
    });
    return new EvaluationPersonCycleDetailResource($personCycleDetail);
  }

  public function recalculateWeights($cyclePersonDetailId, EvaluationPersonCycleDetail $optionalPersonCycleDetail = null): array
  {
    $personCycleDetail = $optionalPersonCycleDetail ?? $this->find($cyclePersonDetailId);

    $allObjectives = EvaluationPersonCycleDetail::where('person_id', $personCycleDetail->person_id)
      ->where('chief_id', $personCycleDetail->chief_id)
      ->where('position_id', $personCycleDetail->position_id)
      ->where('sede_id', $personCycleDetail->sede_id)
      ->where('area_id', $personCycleDetail->area_id)
      ->where('cycle_id', $personCycleDetail->cycle_id)
      ->where('category_id', $personCycleDetail->category_id)
      ->get();

    $fixedObjectives = $allObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === true);
    $nonFixedObjectives = $allObjectives->filter(fn($obj) => (bool)$obj->fixedWeight === false)->values();

    $usedWeight = $fixedObjectives->sum('weight');
    $remaining = max(0, 100 - $usedWeight);
    $count = $nonFixedObjectives->count();

    // Cada objetivo activo debe tener peso >= 1
    $baseWeight = $count > 0 ? max(1.0, round($remaining / $count, 2)) : 0;

    // Calcular la diferencia de redondeo (ej: 33.33 × 3 = 99.99 → diff = 0.01)
    $distributed = round($baseWeight * $count, 2);
    $diff = round($remaining - $distributed, 2);

    foreach ($nonFixedObjectives as $index => $objective) {
      // El último objetivo absorbe la diferencia de redondeo para sumar exactamente 100
      $objectiveWeight = ($index === $count - 1 && $diff != 0)
        ? max(1.0, round($baseWeight + $diff, 2))
        : $baseWeight;

      $objective->update([
        'weight' => $objectiveWeight,
        'fixedWeight' => false,
      ]);
    }

    return ['message' => 'Pesos recalculados correctamente'];
  }


  public function destroy($id)
  {
    $personCycleDetail = $this->find($id);
    DB::transaction(function () use ($personCycleDetail) {
      $clone = $personCycleDetail->replicate();

      // Solo eliminar el EvaluationPerson específico de este detalle
      // NO eliminar EvaluationPersonResult ni EvaluationPersonCompetenceDetail
      // ya que la persona puede tener otros objetivos en el mismo ciclo
      $this->cleanupSingleDetailEvaluation($personCycleDetail);

      $personCycleDetail->delete();
      $this->recalculateWeights($clone->id, $clone);
    });
    return response()->json(['message' => 'Detalle de Ciclo Persona eliminado correctamente']);
  }

  /**
   * Limpia todas las evaluaciones asociadas a un EvaluationPersonCycleDetail antes de eliminarlo
   * Esto previene datos huérfanos en EvaluationPersonResult y EvaluationPersonCompetenceDetail
   *
   * @param EvaluationPersonCycleDetail $detail
   * @return void
   */
  private function cleanupAssociatedEvaluations(EvaluationPersonCycleDetail $detail)
  {
    // Buscar directamente por person_id y cycle_id, sin depender de EvaluationPerson
    // porque este puede estar eliminado o no encontrarse por soft deletes

    // 1. Obtener todas las evaluaciones de este ciclo
    $evaluationIds = Evaluation::where('cycle_id', $detail->cycle_id)
      ->pluck('id');

    // 2. Eliminar los registros de esta persona en esas evaluaciones
    foreach ($evaluationIds as $evaluationId) {
      // IMPORTANTE: Eliminar también EvaluationPerson porque el CASCADE no funciona con SoftDeletes
      EvaluationPerson::where('person_cycle_detail_id', $detail->id)
        ->where('evaluation_id', $evaluationId)
        ->delete();

      // Eliminar EvaluationPersonResult de esta persona en esta evaluación
      EvaluationPersonResult::where('evaluation_id', $evaluationId)
        ->where('person_id', $detail->person_id)
        ->delete();

      // Eliminar EvaluationPersonCompetenceDetail de esta persona en esta evaluación
      EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
        ->where('person_id', $detail->person_id)
        ->delete();
    }
  }

  /**
   * Elimina solo el EvaluationPerson asociado a un detalle específico
   * NO elimina EvaluationPersonResult ni EvaluationPersonCompetenceDetail
   *
   * Se usa cuando se elimina MANUALMENTE un objetivo de una persona.
   * La persona puede tener otros objetivos en el ciclo, por lo que no se debe
   * eliminar toda su información de evaluación.
   *
   * @param EvaluationPersonCycleDetail $detail
   * @return void
   */
  private function cleanupSingleDetailEvaluation(EvaluationPersonCycleDetail $detail)
  {
    // 1. Obtener todas las evaluaciones de este ciclo
    $evaluationIds = Evaluation::where('cycle_id', $detail->cycle_id)
      ->pluck('id');

    // 2. Eliminar SOLO el EvaluationPerson de este detalle específico
    foreach ($evaluationIds as $evaluationId) {
      EvaluationPerson::where('person_cycle_detail_id', $detail->id)
        ->where('evaluation_id', $evaluationId)
        ->delete();
    }

    // NO eliminamos EvaluationPersonResult ni EvaluationPersonCompetenceDetail
    // porque la persona puede tener otros objetivos activos en el mismo ciclo
  }

  /**
   * Limpia todas las evaluaciones asociadas a múltiples EvaluationPersonCycleDetail
   *
   * @param int $personId
   * @param \Illuminate\Support\Collection $details
   * @return void
   */
  private function cleanupAssociatedEvaluationsForPerson(int $personId, $details)
  {
    // Buscar directamente por person_id y cycle_id, sin depender de EvaluationPerson
    // porque este puede estar eliminado o no encontrarse por soft deletes

    // 1. Obtener los cycle_ids únicos de los details
    $cycleIds = $details->pluck('cycle_id')->unique();
    $detailIds = $details->pluck('id');

    // 2. Obtener todas las evaluaciones de estos ciclos
    $evaluationIds = Evaluation::whereIn('cycle_id', $cycleIds)
      ->pluck('id');

    // 3. Eliminar los registros de esta persona en esas evaluaciones
    foreach ($evaluationIds as $evaluationId) {
      // IMPORTANTE: Eliminar EvaluationPerson de estos details porque CASCADE no funciona con SoftDeletes
      EvaluationPerson::whereIn('person_cycle_detail_id', $detailIds)
        ->where('evaluation_id', $evaluationId)
        ->delete();

      // Eliminar EvaluationPersonResult de esta persona en esta evaluación
      EvaluationPersonResult::where('evaluation_id', $evaluationId)
        ->where('person_id', $personId)
        ->delete();

      // Eliminar EvaluationPersonCompetenceDetail de esta persona en esta evaluación
      EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
        ->where('person_id', $personId)
        ->delete();
    }
  }

  /**
   * Devuelve una vista previa del estado de pesos por persona en un ciclo,
   * sin realizar ninguna modificación.
   * Agrupa en: needs_update (suma != 100) y ok (suma == 100).
   *
   * @param int $cycleId
   * @return array
   * @throws Exception
   */
  public function previewWeightsByCycle(int $cycleId): array
  {
    $cycle = EvaluationCycle::find($cycleId);
    if (!$cycle) {
      throw new Exception('Ciclo no encontrado');
    }

    $personGroups = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
      ->whereNull('deleted_at')
      ->select('person_id', 'category_id')
      ->distinct()
      ->get();

    $needsUpdate = [];

    foreach ($personGroups as $group) {
      $objectives = EvaluationPersonCycleDetail::where('person_id', $group->person_id)
        ->where('cycle_id', $cycleId)
        ->where('category_id', $group->category_id)
        ->whereNull('deleted_at')
        ->get(['id', 'person_id', 'person', 'category_id', 'category', 'objective_id', 'objective', 'weight', 'fixedWeight']);

      if ($objectives->isEmpty()) {
        continue;
      }

      $totalWeight = round($objectives->sum('weight'), 2);
      $hasLowWeight = $objectives->contains(fn($o) => $o->weight < 1);

      if ($totalWeight == 100.00 && !$hasLowWeight) {
        continue;
      }

      $reasons = [];
      if ($totalWeight != 100.00) $reasons[] = "suma_incorrecta ({$totalWeight})";
      if ($hasLowWeight) $reasons[] = 'peso_menor_a_1';

      $first = $objectives->first();

      $needsUpdate[] = [
        'person_id' => $first->person_id,
        'person' => $first->person,
        'category_id' => $first->category_id,
        'category' => $first->category,
        'total_weight' => $totalWeight,
        'reasons' => $reasons,
        'objectives' => $objectives->map(fn($o) => [
          'id' => $o->id,
          'objective' => $o->objective,
          'weight' => $o->weight,
          'fixedWeight' => (bool)$o->fixedWeight,
        ])->values(),
      ];
    }

    return [
      'cycle_id' => $cycleId,
      'needs_update_count' => count($needsUpdate),
      'needs_update' => $needsUpdate,
    ];
  }

  /**
   * Regenera los pesos de personas en un ciclo cuya suma no es 100.
   * Omite a quienes ya tienen sus pesos correctamente asignados (suma = 100).
   * Para cada grupo persona+categoría que no sume 100, redistribuye los pesos
   * respetando los objetivos con fixedWeight y distribuyendo el resto equitativamente.
   *
   * @param int $cycleId
   * @return array
   * @throws Exception
   */
  public function regenerateWeightsByCycle(int $cycleId): array
  {
    $cycle = EvaluationCycle::find($cycleId);
    if (!$cycle) {
      throw new Exception('Ciclo no encontrado');
    }

    $personGroups = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
      ->whereNull('deleted_at')
      ->select('person_id', 'category_id')
      ->distinct()
      ->get();

    $updated = [];
    $skipped = [];

    foreach ($personGroups as $group) {
      $objectives = EvaluationPersonCycleDetail::where('person_id', $group->person_id)
        ->where('cycle_id', $cycleId)
        ->where('category_id', $group->category_id)
        ->whereNull('deleted_at')
        ->get();

      if ($objectives->isEmpty()) {
        continue;
      }

      $totalWeight = round($objectives->sum('weight'), 2);
      $hasLowWeight = $objectives->contains(fn($o) => $o->weight < 1);
      $personName = $objectives->first()->person;
      $categoryName = $objectives->first()->category;

      if ($totalWeight == 100.00 && !$hasLowWeight) {
        $skipped[] = [
          'person_id' => $group->person_id,
          'person' => $personName,
          'category' => $categoryName,
          'total_weight' => $totalWeight,
        ];
        continue;
      }

      // Redistribuir pesos: respeta fixedWeight, distribuye el resto equitativamente
      $this->recalculateWeights($objectives->first()->id);

      $newTotal = round(
        EvaluationPersonCycleDetail::where('person_id', $group->person_id)
          ->where('cycle_id', $cycleId)
          ->where('category_id', $group->category_id)
          ->whereNull('deleted_at')
          ->sum('weight'),
        2
      );

      $updated[] = [
        'person_id' => $group->person_id,
        'person' => $personName,
        'category' => $categoryName,
        'previous_total_weight' => $totalWeight,
        'new_total_weight' => $newTotal,
      ];
    }

    return [
      'cycle_id' => $cycleId,
      'updated_count' => count($updated),
      'skipped_count' => count($skipped),
      'updated' => $updated,
      'skipped' => $skipped,
    ];
  }

  /**
   * Retorna los workers elegibles para un ciclo que aún no están asignados.
   * Criterios: activo, fecha_inicio <= cut_off_date, sin evaluationDetails,
   * con supervisor asignado, cargo en categoría del ciclo y no en ciclo aún.
   *
   * @param int $cycleId
   * @return \Illuminate\Support\Collection<Worker>
   * @throws Exception
   */
  public function previewEligibleWorkers(int $cycleId)
  {
    $cycle = EvaluationCycle::find($cycleId);
    if (!$cycle) {
      throw new Exception('Ciclo no encontrado');
    }

    $excludedWorkerIds = EvaluationPersonDetail::whereNull('deleted_at')
      ->pluck('person_id')
      ->unique();

    $categoryIds = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)
      ->whereNull('deleted_at')
      ->pluck('hierarchical_category_id');

    $positionIds = HierarchicalCategoryDetail::whereIn('hierarchical_category_id', $categoryIds)
      ->whereHas('hierarchicalCategory',
        fn($q) => $q->whereNull('deleted_at')->where('excluded_from_evaluation', false)->where('hasObjectives', true)
      )
      ->pluck('position_id')
      ->unique()
      ->filter()
      ->values();

    // Objetivos ya insertados en el ciclo por persona: person_id => [objective_ids]
    $objectivesInCycle = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
      ->whereNull('deleted_at')
      ->get(['person_id', 'objective_id'])
      ->groupBy('person_id')
      ->map(fn($rows) => $rows->pluck('objective_id'));

    $workers = Worker::whereIn('cargo_id', $positionIds)
      ->whereNotIn('id', $excludedWorkerIds)
      ->where('fecha_inicio', '<=', $cycle->cut_off_date)
      ->where('status_id', 22)
      ->whereDoesntHave('evaluationDetails')
      ->whereNotNull('supervisor_id')
      ->with(['position.hierarchicalCategory', 'position.area', 'sede'])
      ->get();

    // Solo incluir workers que tengan al menos un objetivo pendiente de insertar
    return $workers->filter(function (Worker $worker) use ($cycleId, $objectivesInCycle) {
      $category = $worker->position?->hierarchicalCategory;
      if (!$category) {
        return false;
      }

      // Objetivos que el worker debería tener (tiene EvaluationCategoryObjectiveDetail activo)
      $shouldHaveIds = EvaluationCategoryObjectiveDetail::where('category_id', $category->id)
        ->where('person_id', $worker->id)
        ->where('active', 1)
        ->whereNull('deleted_at')
        ->whereHas('objective', fn($q) => $q->where('active', 1))
        ->pluck('objective_id');

      if ($shouldHaveIds->isEmpty()) {
        return false;
      }

      // Objetivos que ya tiene en el ciclo
      $hasIds = $objectivesInCycle->get($worker->id, collect());

      // Es elegible si le falta al menos uno
      return $shouldHaveIds->diff($hasIds)->isNotEmpty();
    })->values();
  }

  /**
   * Inserta múltiples workers en un ciclo individualmente.
   * Devuelve resumen de agregados y errores.
   *
   * @param int $cycleId
   * @param array $workerIds
   * @return array
   * @throws Exception
   */
  public function storeManyByWorker(int $cycleId, array $workerIds): array
  {
    $cycle = EvaluationCycle::find($cycleId);
    if (!$cycle) {
      throw new Exception('Ciclo no encontrado');
    }

    $added = [];
    $errors = [];

    foreach ($workerIds as $workerId) {
      try {
        $this->storeByWorkerAndCycle($cycleId, $workerId);
        $added[] = $workerId;
      } catch (Exception $e) {
        $errors[] = ['worker_id' => $workerId, 'message' => $e->getMessage()];
      }
    }

    if (!empty($added)) {
      $this->revalidateAllPersonsInCycle($cycleId);
    }

    return [
      'added' => $added,
      'errors' => $errors,
      'summary' => [
        'added_count' => count($added),
        'errors_count' => count($errors),
      ],
    ];
  }

  /**
   * Inserta un worker individual en un ciclo, creando sus EvaluationPersonCycleDetail
   * por cada objetivo activo de su categoría jerárquica.
   *
   * @param int $cycleId
   * @param int $workerId
   * @throws Exception
   */
  public function storeByWorkerAndCycle(int $cycleId, int $workerId): void
  {
    $cycle = EvaluationCycle::find($cycleId);
    if (!$cycle) {
      throw new Exception('Ciclo no encontrado');
    }

    $person = Worker::find($workerId);
    if (!$person) {
      throw new Exception("Persona con ID {$workerId} no encontrada");
    }

    $hierarchicalCategory = $person->position?->hierarchicalCategory;
    if (!$hierarchicalCategory) {
      throw new Exception("La persona {$person->nombre_completo} no tiene una categoría jerárquica asignada.");
    }

    $inCycle = EvaluationCycleCategoryDetail::where('cycle_id', $cycleId)
      ->where('hierarchical_category_id', $hierarchicalCategory->id)
      ->whereNull('deleted_at')
      ->exists();

    if (!$inCycle) {
      throw new Exception("La categoría '{$hierarchicalCategory->name}' de la persona {$person->nombre_completo} no está en el ciclo.");
    }

    $evaluatorId = $person->supervisor_id ?? $person->jefe_id;
    if (!$evaluatorId) {
      throw new Exception("La persona {$person->nombre_completo} no tiene un evaluador asignado.");
    }

    $chief = Worker::find($evaluatorId);
    $objectives = $hierarchicalCategory->objectives()->get();
    $previousCycle = EvaluationCycle::where('id', '<', $cycleId)->orderBy('id', 'desc')->first();

    // Pre-cargar los objective_ids que el worker ya tiene en este ciclo para no repetir consultas
    $existingObjectiveIds = EvaluationPersonCycleDetail::where('person_id', $person->id)
      ->where('cycle_id', $cycleId)
      ->whereNull('deleted_at')
      ->pluck('objective_id');

    foreach ($objectives as $objective) {
      // Saltar si este objetivo ya está insertado en el ciclo para esta persona
      if ($existingObjectiveIds->contains($objective->id)) {
        continue;
      }

      $categoryObjective = EvaluationCategoryObjectiveDetail::where('objective_id', $objective->id)
        ->whereHas('objective', fn($q) => $q->where('active', 1))
        ->where('category_id', $hierarchicalCategory->id)
        ->where('person_id', $person->id)
        ->where('active', 1)
        ->whereNull('deleted_at')
        ->first();

      if (!$categoryObjective) {
        continue;
      }

      $goal = 0;
      $weight = 0;

      if ($previousCycle) {
        $prev = EvaluationPersonCycleDetail::where('person_id', $person->id)
          ->where('cycle_id', $previousCycle->id)
          ->where('category_id', $hierarchicalCategory->id)
          ->where('objective_id', $objective->id)
          ->whereNull('deleted_at')
          ->first();
        $goal = $prev?->goal ?? 0;
        $weight = $prev?->weight ?? 0;
      }

      if ($goal === 0) {
        $goal = $categoryObjective->goal ?? 0;
        if ($weight === 0) {
          $weight = $categoryObjective->weight ?? 0;
        }
      }

      if ($goal === 0) {
        $goal = $objective->goalReference;
        if ($weight === 0) {
          $weight = round(100 / $objectives->count(), 2);
        }
      }

      EvaluationPersonCycleDetail::create([
        'person_id' => $person->id,
        'chief_id' => $evaluatorId,
        'position_id' => $person->cargo_id,
        'sede_id' => $person->sede_id,
        'area_id' => $person->area_id,
        'cycle_id' => $cycleId,
        'category_id' => $hierarchicalCategory->id,
        'objective_id' => $objective->id,
        'isAscending' => $objective->isAscending,
        'person' => $person->nombre_completo,
        'chief' => $chief?->nombre_completo ?? '',
        'position' => $person->position?->name ?? '',
        'sede' => $person->sede?->abreviatura ?? '',
        'area' => $person->position?->area?->name ?? '',
        'category' => $hierarchicalCategory->name,
        'objective' => $objective->name,
        'goal' => $goal,
        'weight' => $weight,
        'metric' => $objective->metric?->name
          ?? throw new Exception("El objetivo {$objective->name} no tiene una métrica asignada."),
        'end_date_objectives' => $cycle->end_date_objectives,
      ]);
    }
  }

  /**
   * Obtiene la lista de chiefs únicos de un ciclo
   * @param int $cycleId
   * @return \Illuminate\Support\Collection
   */
  public function getChiefsByCycle(int $cycleId)
  {
    // Obtener los IDs únicos de chiefs del ciclo
    $chiefIds = EvaluationPersonCycleDetail::where('cycle_id', $cycleId)
      ->whereNotNull('chief_id')
      ->pluck('chief_id')
      ->unique()
      ->filter()
      ->values();

    // Buscar los Workers por ID
    $chiefs = Worker::whereIn('id', $chiefIds)
      ->working()
      ->with(['position.hierarchicalCategory', 'position.area', 'sede'])
      ->get();

    return $chiefs;
  }
}
