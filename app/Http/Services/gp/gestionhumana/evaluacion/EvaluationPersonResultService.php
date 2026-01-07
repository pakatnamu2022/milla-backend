<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonResultResource;
use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonResultService extends BaseService
{
  protected $exportService;
  protected EvaluationPersonCycleDetailService $personCycleDetailService;

  public function __construct(
    ExportService                      $exportService,
    EvaluationPersonCycleDetailService $personCycleDetailService
  )
  {
    $this->exportService = $exportService;
    $this->personCycleDetailService = $personCycleDetailService;
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, EvaluationPersonResult::class);
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationPersonResult::class,
      $request,
      EvaluationPersonResult::filters,
      EvaluationPersonResult::sorts,
      EvaluationPersonResultResource::class,
    );
  }

  public function getActiveEvaluation()
  {
    $evaluation = Evaluation::where('status', 1)->first();
    if (!$evaluation) {
      throw new Exception('No hay una evaluación activa en este momento.');
    }
    return $evaluation;
  }

  public function getEvaluationsByPersonToEvaluate(Request $request, int $id)
  {
    $activeEvaluation = $this->getActiveEvaluation();

    // Get person_ids where the person is evaluator in competences
    $competencePersonIds = EvaluationPersonCompetenceDetail::where('evaluation_id', $activeEvaluation->id)
      ->where('evaluator_id', $id)
      ->pluck('person_id')
      ->unique();

    // Get person_ids where the person is chief in objectives
    $objectivePersonIds = EvaluationPerson::where('evaluation_id', $activeEvaluation->id)
      ->where('chief_id', $id)
      ->pluck('person_id')
      ->unique();

    // Merge both collections and get unique person_ids
    $allEvaluations = $competencePersonIds->merge($objectivePersonIds)->unique();

    return $this->getFilteredResults(
      EvaluationPersonResult::where('evaluation_id', $activeEvaluation->id)
        ->whereIn('person_id', $allEvaluations),
      $request,
      EvaluationPersonResult::filters,
      EvaluationPersonResult::sorts,
      EvaluationPersonResultResource::class,
      ['showExtra' => [true]]
    );
  }

  /**
   * Dashboard del Líder - Vista consolidada del equipo
   * Devuelve estadísticas agregadas del equipo del líder autenticado
   * OPTIMIZADO: Usa datos precalculados de EvaluationPersonDashboard
   * @throws Exception
   */
  public function getLeaderDashboard(Request $request, int $evaluation_id)
  {
    // Obtener el usuario autenticado
    $authenticatedUser = auth()->user();
    if (!$authenticatedUser) {
      throw new Exception('Usuario no autenticado');
    }

    $chief_id = $authenticatedUser->partner_id;

    // Validar que existe la evaluación
    $evaluation = Evaluation::with([
      'finalParameter.details',
      'objectiveParameter.details',
      'competenceParameter.details'
    ])->findOrFail($evaluation_id);

    // PASO 1: Obtener person_ids de colaboradores desde EvaluationPerson
    $collaboratorPersonIds = EvaluationPerson::where('evaluation_id', $evaluation_id)
      ->where('chief_id', $chief_id)
      ->pluck('person_id')
      ->unique()
      ->values();

    // Si no hay colaboradores, verificar qué chief_ids existen
    if ($collaboratorPersonIds->isEmpty()) {
      $uniqueChiefIds = EvaluationPerson::where('evaluation_id', $evaluation_id)
        ->pluck('chief_id')
        ->unique()
        ->values();
    }

    // PASO 2: Obtener los resultados consolidados de EvaluationPersonResult
    $teamResults = EvaluationPersonResult::with([
      'person.position.hierarchicalCategory',
      'person.position.area',
      'person.sede',
      'details.personCycleDetail.objective.metric',
      'competenceDetails.competence',
      'competenceDetails.subCompetence'
    ])
      ->where('evaluation_id', $evaluation_id)
      ->whereIn('person_id', $collaboratorPersonIds)
      ->get();

    if ($teamResults->isEmpty()) {
      throw new Exception('No se encontraron resultados de evaluación para los colaboradores del líder autenticado.');
    }

    // Obtener dashboards precalculados para el equipo
    $personIds = $teamResults->pluck('person_id');
    $dashboards = EvaluationPersonDashboard::where('evaluation_id', $evaluation_id)
      ->whereIn('person_id', $personIds)
      ->get()
      ->keyBy('person_id');

    // 1. RESUMEN EJECUTIVO DEL EQUIPO (usando datos precalculados)
    $totalCollaborators = $teamResults->count();
    $completed = 0;
    $inProgress = 0;
    $notStarted = 0;

    foreach ($teamResults as $result) {
      $dashboard = $dashboards->get($result->person_id);
      if ($dashboard && $dashboard->last_calculated_at) {
        // Usar datos precalculados del dashboard
        if ($dashboard->is_completed) {
          $completed++;
        } elseif ($dashboard->completion_rate > 0) {
          $inProgress++;
        } else {
          $notStarted++;
        }
      } else {
        // Fallback: usar cálculo en tiempo real si no hay dashboard
        if ($result->is_completed) {
          $completed++;
        } elseif ($result->completion_percentage > 0) {
          $inProgress++;
        } else {
          $notStarted++;
        }
      }
    }

    $teamSummary = [
      'total_collaborators' => $totalCollaborators,
      'completed' => $completed,
      'in_progress' => $inProgress,
      'not_started' => $notStarted,
      'completion_percentage' => round(($completed / $totalCollaborators) * 100, 2),
      'progress_percentage' => round((($completed + $inProgress) / $totalCollaborators) * 100, 2),
      'average_result' => round($teamResults->where('result', '>', 0)->avg('result'), 2),
      'average_objectives' => round($teamResults->where('objectivesResult', '>', 0)->avg('objectivesResult'), 2),
      'average_competences' => round($teamResults->where('competencesResult', '>', 0)->avg('competencesResult'), 2),
    ];

    // 2. LISTA DE COLABORADORES CON ESTADO (usando dashboards precalculados)
    $collaboratorsList = $teamResults->map(function ($result) use ($dashboards) {
      $dashboard = $dashboards->get($result->person_id);

      // Determinar estado usando dashboard precalculado si está disponible
      if ($dashboard && $dashboard->last_calculated_at) {
        $isCompleted = $dashboard->is_completed;
        $completionRate = $dashboard->completion_rate;
        $status = $dashboard->progress_status;
        $statusLabel = $this->getStatusLabel($status);
      } else {
        // Fallback
        $isCompleted = $result->is_completed;
        $completionRate = $result->completion_percentage * 100; // Ya es un número (0-1)
        $status = $isCompleted ? 'completed' : ($completionRate > 0 ? 'in_progress' : 'not_started');
        $statusLabel = $isCompleted ? 'Completado' : ($completionRate > 0 ? 'En Progreso' : 'Sin Iniciar');
      }

      return [
        'id' => $result->id,
        'person_id' => $result->person_id,
        'name' => $result->name,
        'dni' => $result->dni,
        'position' => $result->position,
        'area' => $result->area,
        'sede' => $result->sede,
        'hierarchical_category' => $result->hierarchical_category,
        'result' => round($result->result, 2),
        'objectives_result' => round($result->objectivesResult, 2),
        'competences_result' => round($result->competencesResult, 2),
        'completion_rate' => round($completionRate, 2),
        'is_completed' => $isCompleted,
        'status' => $status,
        'status_label' => $statusLabel,
        'last_calculated_at' => $dashboard?->last_calculated_at,
      ];
    });

    // 3. DISTRIBUCIÓN DE CALIFICACIONES
    $finalParameter = $evaluation->finalParameter;
    $distribution = [];

    foreach ($finalParameter->details as $range) {
      $count = $teamResults->filter(function ($result) use ($range) {
        return $result->result >= $range->from && $result->result < $range->to;
      })->count();

      $distribution[] = [
        'label' => $range->label,
        'from' => $range->from,
        'to' => $range->to,
        'count' => $count,
        'percentage' => $totalCollaborators > 0 ? round(($count / $totalCollaborators) * 100, 2) : 0,
      ];
    }

    // 4. BRECHAS DE COMPETENCIAS DEL EQUIPO (usando dashboards precalculados)
    $competenceGaps = $this->calculateTeamCompetenceGapsFromDashboards($teamResults, $dashboards, $evaluation);

    // 5. OBJETIVOS DEL EQUIPO (Agregado por objetivo)
    $objectivesProgress = $this->calculateTeamObjectivesProgress($teamResults);

    // 6. ALERTAS Y ACCIONES PENDIENTES
    $alerts = [
      'not_started_count' => $notStarted,
      'overdue_count' => $teamResults->filter(function ($result) use ($evaluation, $dashboards) {
        $dashboard = $dashboards->get($result->person_id);
        $isCompleted = $dashboard && $dashboard->last_calculated_at
          ? $dashboard->is_completed
          : $result->is_completed;
        return !$isCompleted && now() > $evaluation->end_date;
      })->count(),
      'low_performance_count' => $teamResults->where('result', '>', 0)
        ->where('result', '<', 70)->count(),
      'evaluation_end_date' => $evaluation->end_date,
      'days_remaining' => now() < $evaluation->end_date ? now()->diffInDays($evaluation->end_date) : 0,
      'is_active' => $evaluation->status == 1,
    ];

    // 7. MÉTRICAS POR ÁREA (Si hay múltiples áreas en el equipo)
    $areaMetrics = $teamResults->groupBy('area')->map(function ($areaGroup, $areaName) use ($dashboards) {
      $completedCount = $areaGroup->filter(function ($result) use ($dashboards) {
        $dashboard = $dashboards->get($result->person_id);
        return $dashboard && $dashboard->last_calculated_at
          ? $dashboard->is_completed
          : $result->is_completed;
      })->count();

      return [
        'area' => $areaName ?? 'Sin área',
        'total' => $areaGroup->count(),
        'average_result' => round($areaGroup->where('result', '>', 0)->avg('result'), 2),
        'completed' => $completedCount,
      ];
    })->values();

    // 8. MÉTRICAS POR CATEGORÍA JERÁRQUICA
    $categoryMetrics = $teamResults->groupBy('hierarchical_category')->map(function ($categoryGroup, $categoryName) use ($dashboards) {
      $completedCount = $categoryGroup->filter(function ($result) use ($dashboards) {
        $dashboard = $dashboards->get($result->person_id);
        return $dashboard && $dashboard->last_calculated_at
          ? $dashboard->is_completed
          : $result->is_completed;
      })->count();

      return [
        'category' => $categoryName ?? 'Sin categoría',
        'total' => $categoryGroup->count(),
        'average_result' => round($categoryGroup->where('result', '>', 0)->avg('result'), 2),
        'completed' => $completedCount,
      ];
    })->values();

    return [
      'evaluation' => new EvaluationResource($evaluation),
      'team_summary' => $teamSummary,
      'collaborators' => $collaboratorsList,
      'distribution' => $distribution,
      'competence_gaps' => $competenceGaps,
      'objectives_progress' => $objectivesProgress,
      'alerts' => $alerts,
      'area_metrics' => $areaMetrics,
      'category_metrics' => $categoryMetrics,
    ];
  }

  /**
   * Obtener etiqueta de estado en español
   */
  private function getStatusLabel($status)
  {
    $labels = [
      'completado' => 'Completado',
      'en_progreso' => 'En Progreso',
      'sin_iniciar' => 'Sin Iniciar',
      'completed' => 'Completado',
      'in_progress' => 'En Progreso',
      'not_started' => 'Sin Iniciar',
    ];

    return $labels[$status] ?? 'Sin Iniciar';
  }

  /**
   * Calcula las brechas de competencias del equipo usando dashboards precalculados
   */
  private function calculateTeamCompetenceGapsFromDashboards($teamResults, $dashboards, $evaluation)
  {
    $competenceScores = [];

    foreach ($teamResults as $result) {
      $dashboard = $dashboards->get($result->person_id);

      // Si hay dashboard precalculado con grouped_competences, usarlo
      if ($dashboard && $dashboard->last_calculated_at && $dashboard->grouped_competences) {
        foreach ($dashboard->grouped_competences as $competence) {
          $competenceId = $competence['competence_id'];
          $competenceName = $competence['competence_name'];
          $averageResult = $competence['average_result'] ?? 0;

          if (!isset($competenceScores[$competenceId])) {
            $competenceScores[$competenceId] = [
              'competence_id' => $competenceId,
              'competence_name' => $competenceName,
              'total_score' => 0,
              'count' => 0,
            ];
          }

          if ($averageResult > 0) {
            $competenceScores[$competenceId]['total_score'] += $averageResult;
            $competenceScores[$competenceId]['count']++;
          }
        }
      } else {
        // Fallback: usar competenceDetails directamente
        foreach ($result->competenceDetails as $detail) {
          $competenceId = $detail->competence_id;
          $competenceName = $detail->competence;

          if (!isset($competenceScores[$competenceId])) {
            $competenceScores[$competenceId] = [
              'competence_id' => $competenceId,
              'competence_name' => $competenceName,
              'total_score' => 0,
              'count' => 0,
            ];
          }

          if ($detail->result > 0) {
            $competenceScores[$competenceId]['total_score'] += $detail->result;
            $competenceScores[$competenceId]['count']++;
          }
        }
      }
    }

    // Calcular promedios y ordenar
    $competenceGaps = collect($competenceScores)->map(function ($comp) use ($evaluation) {
      $average = $comp['count'] > 0 ? round($comp['total_score'] / $comp['count'], 2) : 0;
      $maxScore = $evaluation->competenceParameter->details->last()->to ?? 100;

      return [
        'competence_id' => $comp['competence_id'],
        'competence_name' => $comp['competence_name'],
        'average_score' => $average,
        'max_score' => $maxScore,
        'gap_percentage' => $maxScore > 0 ? round((($maxScore - $average) / $maxScore) * 100, 2) : 0,
        'evaluations_count' => $comp['count'],
        'status' => $average >= 80 ? 'strong' : ($average >= 70 ? 'adequate' : 'needs_improvement'),
      ];
    })->sortBy('average_score')->values()->toArray();

    return $competenceGaps;
  }

  /**
   * Calcula las brechas de competencias del equipo (competencias con menor puntuación)
   */
  private function calculateTeamCompetenceGaps($teamResults, $evaluation)
  {
    $competenceScores = [];

    foreach ($teamResults as $result) {
      foreach ($result->competenceDetails as $detail) {
        $competenceId = $detail->competence_id;
        $competenceName = $detail->competence;

        if (!isset($competenceScores[$competenceId])) {
          $competenceScores[$competenceId] = [
            'competence_id' => $competenceId,
            'competence_name' => $competenceName,
            'total_score' => 0,
            'count' => 0,
          ];
        }

        if ($detail->result > 0) {
          $competenceScores[$competenceId]['total_score'] += $detail->result;
          $competenceScores[$competenceId]['count']++;
        }
      }
    }

    // Calcular promedios y ordenar
    $competenceGaps = collect($competenceScores)->map(function ($comp) use ($evaluation) {
      $average = $comp['count'] > 0 ? round($comp['total_score'] / $comp['count'], 2) : 0;
      $maxScore = $evaluation->competenceParameter->details->last()->to ?? 100;

      return [
        'competence_id' => $comp['competence_id'],
        'competence_name' => $comp['competence_name'],
        'average_score' => $average,
        'max_score' => $maxScore,
        'gap_percentage' => $maxScore > 0 ? round((($maxScore - $average) / $maxScore) * 100, 2) : 0,
        'evaluations_count' => $comp['count'],
        'status' => $average >= 80 ? 'strong' : ($average >= 70 ? 'adequate' : 'needs_improvement'),
      ];
    })->sortBy('average_score')->values()->toArray();

    return $competenceGaps;
  }

  /**
   * Calcula el progreso de objetivos del equipo (agregado por objetivo)
   */
  private function calculateTeamObjectivesProgress($teamResults)
  {
    $objectivesData = [];

    foreach ($teamResults as $result) {
      foreach ($result->details as $detail) {
        if (!$detail->personCycleDetail || !$detail->personCycleDetail->objective) {
          continue;
        }

        $objective = $detail->personCycleDetail->objective;
        $objectiveId = $objective->id;
        $objectiveName = $objective->name;

        if (!isset($objectivesData[$objectiveId])) {
          $objectivesData[$objectiveId] = [
            'objective_id' => $objectiveId,
            'objective_name' => $objectiveName,
            'metric' => $objective->metric->name ?? 'N/A',
            'total_evaluations' => 0,
            'completed_evaluations' => 0,
            'total_compliance' => 0,
            'total_result' => 0,
          ];
        }

        $objectivesData[$objectiveId]['total_evaluations']++;

        if ($detail->wasEvaluated) {
          $objectivesData[$objectiveId]['completed_evaluations']++;
          $objectivesData[$objectiveId]['total_compliance'] += $detail->compliance ?? 0;
          $objectivesData[$objectiveId]['total_result'] += $detail->result ?? 0;
        }
      }
    }

    // Calcular promedios
    $objectivesProgress = collect($objectivesData)->map(function ($obj) {
      $completedCount = $obj['completed_evaluations'];

      return [
        'objective_id' => $obj['objective_id'],
        'objective_name' => $obj['objective_name'],
        'metric' => $obj['metric'],
        'total_evaluations' => $obj['total_evaluations'],
        'completed_evaluations' => $completedCount,
        'completion_rate' => $obj['total_evaluations'] > 0 ?
          round(($completedCount / $obj['total_evaluations']) * 100, 2) : 0,
        'average_compliance' => $completedCount > 0 ?
          round($obj['total_compliance'] / $completedCount, 2) : 0,
        'average_result' => $completedCount > 0 ?
          round($obj['total_result'] / $completedCount, 2) : 0,
        'status' => $this->getObjectiveStatus($completedCount, $obj['total_evaluations']),
      ];
    })->sortByDesc('average_result')->values()->toArray();

    return $objectivesProgress;
  }

  /**
   * Determina el estado de un objetivo basado en su tasa de cumplimiento
   */
  private function getObjectiveStatus($completed, $total)
  {
    if ($total == 0) return 'not_applicable';

    $rate = ($completed / $total) * 100;

    if ($rate >= 80) return 'on_track';
    if ($rate >= 50) return 'at_risk';
    return 'behind';
  }

  public function getByPersonAndEvaluation($data)
  {
    $person_id = $data['person_id'];
    $evaluation_id = $data['evaluation_id'];

    $query = EvaluationPersonResult::where('person_id', $person_id)
      ->where('evaluation_id', $evaluation_id);

    $count = $query->count();

    if ($count === 0) {
      throw new Exception('Evaluación de persona no encontrada');
    }
    if ($count > 1) {
      throw new Exception('Se encontraron múltiples evaluaciones para la persona y evaluación especificadas');
    }

    $evaluationPerson = $query->first();
    return EvaluationPersonResultResource::make($evaluationPerson)->showExtra();
  }

  public function find($id)
  {
    $evaluationCompetence = EvaluationPersonResult::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Persona de Evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store($data)
  {
    $evaluationMetric = EvaluationPersonResult::create($data);
    return new EvaluationPersonResultResource($evaluationMetric);
  }

  public function storeMany($evaluationId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);
    $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);
    $categories = EvaluationCycleCategoryDetail::where('cycle_id', $cycle->id)->get();

    $ids = EvaluationPersonResult::where('evaluation_id', $evaluation->id)->pluck('id');
    EvaluationPersonResult::destroy($ids);

    DB::transaction(function () use ($categories, $evaluation, $cycle) {
      foreach ($categories as $category) {
        $excludedIds = EvaluationPersonDetail::all();
        $hierarchicalCategory = HierarchicalCategory
          ::where('id', $category->hierarchical_category_id)
          ->with('workers') // Sin constraint, obtiene todos los workers
          ->first();

        // Verificar que existe y tiene workers
        if ($hierarchicalCategory && $hierarchicalCategory->workers->isNotEmpty()) {
          foreach ($hierarchicalCategory->workers as $person) {
            if ($excludedIds->contains('person_id', $person->id)) {
              continue; // Saltar personas sin ID o en la lista de excluidos
            }
            if ($person->fecha_inicio <= $cycle->cut_off_date) {
              $objectivesPercentage = $hierarchicalCategory->hasObjectives ? $evaluation->objectivesPercentage : 0;
              $competencesPercentage = $hierarchicalCategory->hasObjectives ? $evaluation->competencesPercentage : 100;

              /**
               * TODO: Revisar la lógica de asignación de evaluador
               * Actualmente asigna el evaluador si existe, sino el jefe directo
               */
              $evaluator = $person->evaluator ?? throw new Exception('Store Many: La persona ' . $person->nombre_completo . ' de la categoría ' . $person->position->hierarchicalCategory->name . ' no tiene un evaluador asignado.');

              $data = [
                'person_id' => $person->id,
                'evaluation_id' => $evaluation->id,
                'objectivesPercentage' => $objectivesPercentage,
                'competencesPercentage' => $competencesPercentage,
                'objectivesResult' => 0,
                'competencesResult' => 0,
                'status' => 0,
                'result' => 0,
                'name' => $person->nombre_completo,
                'dni' => $person->vat,
                'hierarchical_category' => $person->position?->hierarchicalCategory?->name,
                'position' => $person->position?->name,
                'area' => $person->position?->area?->name,
                'sede' => $person->sede?->abreviatura,
                'boss' => $evaluator->nombre_completo,
                'boss_dni' => $evaluator->vat,
                'boss_hierarchical_category' => $evaluator->position?->hierarchicalCategory?->name ?? "-",
                'boss_position' => $evaluator->position?->name,
                'boss_area' => $evaluator->position?->area?->name,
                'boss_sede' => $evaluator->sede?->abreviatura,
                'comments' => null,
              ];
              EvaluationPersonResult::create($data);
            }
          }
        }
      }
    });

    return ['message' => 'Personas de Evaluación creadas correctamente'];
  }

  public function show($id)
  {
    return EvaluationPersonResultResource::make($this->find($id))->showExtra();
  }

  public function update($data)
  {
    $evaluationCompetence = $this->find($data['id']);
    $evaluationCompetence->update($data);
    return new EvaluationPersonResultResource($evaluationCompetence);
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);
    DB::transaction(function () use ($evaluationCompetence) {
      $evaluationCompetence->delete();
    });
    return response()->json(['message' => 'Persona de Evaluación eliminada correctamente']);
  }

  /**
   * Regenera completamente la evaluación de una persona
   * - Elimina y regenera EvaluationPersonCycleDetail con supervisor actualizado
   * - Elimina y regenera EvaluationPerson desde personCycleDetail regenerado
   * - Elimina y regenera competencias con evaluadores actualizados
   * - Actualiza EvaluationPersonResult con información actualizada del supervisor
   */
  public function regeneratePersonEvaluation(int $personId, int $evaluationId)
  {
    return DB::transaction(function () use ($personId, $evaluationId) {

      $evaluation = Evaluation::findOrFail($evaluationId);
      $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);
      $person = Worker::findOrFail($personId);

      // PASO 1: Eliminar todos los datos existentes de esta persona en esta evaluación

      // 1.1. Eliminar competencias
      EvaluationPersonCompetenceDetail::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->delete();

      // 1.2. Eliminar EvaluationPerson (objetivos)
      EvaluationPerson::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->delete();

      // PASO 2: Regenerar EvaluationPersonCycleDetail con datos actualizados usando el servicio
      // El servicio se encarga de eliminar y regenerar los personCycleDetail
      $personCycleDetails = $this->personCycleDetailService->regenerateForPerson($cycle->id, $person->id);

      if ($personCycleDetails->isEmpty()) {
        throw new Exception('No se pudieron regenerar objetivos (personCycleDetail) para esta persona. Verifica que la persona tenga objetivos asignados en su categoría jerárquica.');
      }

      // PASO 3: Regenerar EvaluationPerson desde personCycleDetail regenerado
      foreach ($personCycleDetails as $detail) {
        $data = [
          'person_id' => $detail->person_id,
          'chief_id' => $detail->chief_id,
          'chief' => $detail->chief,
          'person_cycle_detail_id' => $detail->id,
          'evaluation_id' => $evaluation->id,
          'result' => 0,
          'compliance' => 0,
          'qualification' => 0,
          'objective_parameter_id' => $detail->parameter_id ?? null,
          'period_id' => $detail->period_id ?? null,
        ];
        EvaluationPerson::create($data);
      }

      // PASO 4: Regenerar competencias
      $this->regeneratePersonCompetences($evaluation, $person);

      // PASO 5: Actualizar EvaluationPersonResult con información actualizada
      $personResult = EvaluationPersonResult::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->first();

      if ($personResult) {
        // Obtener el evaluador actualizado
        $evaluator = $person->evaluator;
        if (!$evaluator) {
          throw new Exception('La persona ' . $person->nombre_completo . ' no tiene un evaluador asignado.');
        }

        // Obtener la categoría jerárquica
        $hierarchicalCategory = $person->position?->hierarchicalCategory;
        $objectivesPercentage = $hierarchicalCategory?->hasObjectives ? $evaluation->objectivesPercentage : 0;
        $competencesPercentage = $hierarchicalCategory?->hasObjectives ? $evaluation->competencesPercentage : 100;

        $personResult->update([
          'objectivesPercentage' => $objectivesPercentage,
          'competencesPercentage' => $competencesPercentage,
          'objectivesResult' => 0,
          'competencesResult' => 0,
          'status' => 0,
          'result' => 0,
          'comments' => null,
          'name' => $person->nombre_completo,
          'dni' => $person->vat,
          'hierarchical_category' => $person->position?->hierarchicalCategory?->name,
          'position' => $person->position?->name,
          'area' => $person->position?->area?->name,
          'sede' => $person->sede?->abreviatura,
          'boss' => $evaluator->nombre_completo,
          'boss_dni' => $evaluator->vat,
          'boss_hierarchical_category' => $evaluator->position?->hierarchicalCategory?->name ?? "-",
          'boss_position' => $evaluator->position?->name,
          'boss_area' => $evaluator->position?->area?->name,
          'boss_sede' => $evaluator->sede?->abreviatura,
        ]);
      }

      // PASO 6: Reset EvaluationPersonDashboard
      $dashboard = EvaluationPersonDashboard::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->first();

      if ($dashboard) {
        $dashboard->resetStats();
      }

      return [
        'message' => 'Evaluación del colaborador regenerada completamente desde cero',
        'details' => [
          'cycle_details_regenerated' => $personCycleDetails->count(),
          'objectives_regenerated' => $personCycleDetails->count(),
          'evaluator_updated' => $evaluator->nombre_completo ?? 'N/A',
        ]
      ];
    });
  }

  /**
   * Regenera las competencias de una persona en una evaluación
   * Basado en la lógica de EvaluationService::asignarCompetenciasAPersona
   */
  private function regeneratePersonCompetences(Evaluation $evaluacion, $persona)
  {
    // Constantes de tipos de evaluador
    $TIPO_EVALUADOR_JEFE = 0;
    $TIPO_EVALUADOR_AUTOEVALUACION = 1;
    $TIPO_EVALUADOR_COMPANEROS = 2;
    $TIPO_EVALUADOR_REPORTES = 3;

    // Verificar si tiene jefe
    $tieneJefe = $persona->jefe_id !== null;

    // Verificar si tiene subordinados
    $tieneSubordinados = \App\Models\gp\gestionhumana\personal\Worker::where('jefe_id', $persona->id)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->exists();

    // Obtener competencias asignadas a la persona
    $competenciasData = $this->obtenerCompetenciasParaPersona($persona);

    foreach ($competenciasData as $competenciaData) {
      // 1. Autoevaluación (solo si el peso es mayor a 0)
      if ($evaluacion->selfEvaluation && $evaluacion->self_weight > 0) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->id,
          $TIPO_EVALUADOR_AUTOEVALUACION
        );
      }

      // 2. Evaluación del jefe directo (solo si el peso es mayor a 0)
      if ($tieneJefe && $evaluacion->leadership_weight > 0) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->jefe_id,
          $TIPO_EVALUADOR_JEFE
        );
      }

      // 3. Evaluación de compañeros (solo si está habilitada y el peso es mayor a 0)
      if ($evaluacion->partnersEvaluation && $evaluacion->par_weight > 0) {
        $partners = $this->obtenerCompanerosPorEvaluationParEvaluator($persona);
        foreach ($partners as $partner) {
          $this->crearDetalleCompetencia(
            $evaluacion->id,
            $persona,
            $competenciaData,
            $partner->id,
            $TIPO_EVALUADOR_COMPANEROS
          );
        }
      }

      // 4. Evaluación de reportes directos (solo si el peso es mayor a 0 y tiene subordinados)
      if ($tieneSubordinados && $evaluacion->report_weight > 0) {
        $subordinados = \App\Models\gp\gestionhumana\personal\Worker::where('jefe_id', $persona->id)
          ->where('status_deleted', 1)
          ->where('status_id', 22)
          ->get();

        foreach ($subordinados as $subordinado) {
          $this->crearDetalleCompetencia(
            $evaluacion->id,
            $persona,
            $competenciaData,
            $subordinado->id,
            $TIPO_EVALUADOR_REPORTES
          );
        }
      }
    }
  }

  /**
   * Crear detalle de competencia
   */
  private function crearDetalleCompetencia($evaluacionId, $persona, $competenciaData, $evaluadorId, $tipoEvaluador)
  {
    $evaluador = \App\Models\gp\gestionhumana\personal\Worker::find($evaluadorId);

    if (!$evaluador || !$competenciaData) {
      return;
    }

    EvaluationPersonCompetenceDetail::create([
      'evaluation_id' => $evaluacionId,
      'evaluator_id' => $evaluador->id,
      'person_id' => $persona->id,
      'competence_id' => $competenciaData['competence_id'],
      'sub_competence_id' => $competenciaData['sub_competence_id'],
      'person' => $persona->nombre_completo,
      'evaluator' => $evaluador->nombre_completo,
      'competence' => $competenciaData['competence_name'],
      'sub_competence' => $competenciaData['sub_competence_name'],
      'evaluatorType' => $tipoEvaluador,
      'result' => 0
    ]);
  }

  /**
   * Obtener competencias para una persona según su categoría jerárquica
   */
  private function obtenerCompetenciasParaPersona($persona)
  {
    $competenciasAsignadas = DB::table('gh_evaluation_category_competence')
      ->join('gh_config_competencias', 'gh_evaluation_category_competence.competence_id', '=', 'gh_config_competencias.id')
      ->join('gh_config_subcompetencias', 'gh_config_competencias.id', '=', 'gh_config_subcompetencias.competencia_id')
      ->join('gh_hierarchical_category_detail', 'gh_evaluation_category_competence.category_id', '=', 'gh_hierarchical_category_detail.hierarchical_category_id')
      ->where('gh_evaluation_category_competence.person_id', $persona->id)
      ->where('gh_evaluation_category_competence.active', 1)
      ->where('gh_hierarchical_category_detail.position_id', $persona->cargo_id)
      ->whereNull('gh_config_competencias.deleted_at')
      ->whereNull('gh_config_subcompetencias.deleted_at')
      ->whereNull('gh_evaluation_category_competence.deleted_at')
      ->whereNull('gh_hierarchical_category_detail.deleted_at')
      ->select([
        'gh_config_competencias.id as competence_id',
        'gh_config_competencias.nombre as competence_name',
        'gh_config_subcompetencias.id as sub_competence_id',
        'gh_config_subcompetencias.nombre as sub_competence_name'
      ])
      ->get()
      ->toArray();

    return array_map(function ($item) {
      return (array)$item;
    }, $competenciasAsignadas);
  }

  /**
   * Obtener compañeros desde EvaluationParEvaluator
   */
  private function obtenerCompanerosPorEvaluationParEvaluator($persona)
  {
    $parEvaluators = \App\Models\gp\gestionhumana\evaluacion\EvaluationParEvaluator::where('worker_id', $persona->id)->get();

    if ($parEvaluators->isEmpty()) {
      return collect();
    }

    $mateIds = $parEvaluators->pluck('mate_id')->toArray();

    return \App\Models\gp\gestionhumana\personal\Worker::whereIn('id', $mateIds)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->get();
  }

  /**
   * Obtiene la lista de bosses únicos de una evaluación
   * @param int $evaluationId
   * @return \Illuminate\Support\Collection
   */
  public function getBossesByEvaluation(int $evaluationId)
  {
    // Obtener los DNIs únicos de bosses de la evaluación
    $bossDnis = EvaluationPersonResult::where('evaluation_id', $evaluationId)
      ->whereNotNull('boss_dni')
      ->pluck('boss_dni')
      ->unique()
      ->filter()
      ->values();

    // Buscar los Workers por DNI
    $bosses = Worker::whereIn('vat', $bossDnis)
      ->with(['position.hierarchicalCategory', 'position.area', 'sede'])
      ->get();

    return $bosses;
  }
}
