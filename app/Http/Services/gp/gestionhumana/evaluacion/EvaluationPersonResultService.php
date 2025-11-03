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
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonResultService extends BaseService
{
  protected $exportService;
  
  public function __construct(
    ExportService $exportService
  )
  {
    $this->exportService = $exportService;
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

  public function getTeamByChief(Request $request, int $chief_id)
  {
    $activeEvaluation = Evaluation::where('status', 1)->first();
    if (!$activeEvaluation) {
      return null;
    }

    return $this->getFilteredResults(
      EvaluationPersonResult::whereHas('person', function ($query) use ($chief_id) {
        $query->where('supervisor_id', $chief_id)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })->where('evaluation_id', $activeEvaluation->id),
      $request,
      EvaluationPersonResult::filters,
      EvaluationPersonResult::sorts,
      EvaluationPersonResultResource::class,
      ['showExtra' => [true]] // 👈 Configuración del Resource
    );
  }

  /**
   * Dashboard del Líder - Vista consolidada del equipo
   * Devuelve estadísticas agregadas del equipo del líder autenticado
   */
  public function getLeaderDashboard(Request $request, int $evaluation_id)
  {
    // Obtener el usuario autenticado
    $authenticatedUser = auth()->user();
    if (!$authenticatedUser) {
      throw new Exception('Usuario no autenticado');
    }

    $chief_id = $authenticatedUser->person_id;

    // Validar que existe la evaluación
    $evaluation = Evaluation::with([
      'finalParameter.details',
      'objectiveParameter.details',
      'competenceParameter.details'
    ])->findOrFail($evaluation_id);

    // Obtener todos los resultados del equipo
    $teamResults = EvaluationPersonResult::with([
      'person.position.hierarchicalCategory',
      'person.position.area',
      'person.sede',
      'details.personCycleDetail.objective.metric',
      'competenceDetails.competence',
      'competenceDetails.subCompetence'
    ])
      ->whereHas('person', function ($query) use ($chief_id) {
        $query->where('supervisor_id', $chief_id)
          ->where('status_deleted', 1)
          ->where('status_id', 22);
      })
      ->where('evaluation_id', $evaluation_id)
      ->get();

    if ($teamResults->isEmpty()) {
      return [
        'evaluation' => new EvaluationResource($evaluation),
        'team_summary' => [
          'total_collaborators' => 0,
          'completed' => 0,
          'in_progress' => 0,
          'not_started' => 0,
          'completion_percentage' => 0,
        ],
        'message' => 'No se encontraron colaboradores en tu equipo para esta evaluación'
      ];
    }

    // 1. RESUMEN EJECUTIVO DEL EQUIPO
    $totalCollaborators = $teamResults->count();
    $completed = $teamResults->where('is_completed', true)->count();
    $inProgress = $teamResults->where('total_progress', '>', 0)->where('is_completed', false)->count();
    $notStarted = $teamResults->where('total_progress', 0)->count();

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

    // 2. LISTA DE COLABORADORES CON ESTADO
    $collaboratorsList = $teamResults->map(function ($result) {
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
        'total_progress' => $result->total_progress,
        'is_completed' => $result->is_completed,
        'status' => $result->is_completed ? 'completed' : ($result->total_progress > 0 ? 'in_progress' : 'not_started'),
        'status_label' => $result->is_completed ? 'Completado' : ($result->total_progress > 0 ? 'En Progreso' : 'Sin Iniciar'),
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

    // 4. BRECHAS DE COMPETENCIAS DEL EQUIPO (Agregado)
    $competenceGaps = $this->calculateTeamCompetenceGaps($teamResults, $evaluation);

    // 5. OBJETIVOS DEL EQUIPO (Agregado por objetivo)
    $objectivesProgress = $this->calculateTeamObjectivesProgress($teamResults);

    // 6. ALERTAS Y ACCIONES PENDIENTES
    $alerts = [
      'not_started_count' => $notStarted,
      'overdue_count' => $teamResults->where('is_completed', false)
        ->filter(function ($result) use ($evaluation) {
          return now() > $evaluation->end_date;
        })->count(),
      'low_performance_count' => $teamResults->where('result', '>', 0)
        ->where('result', '<', 70)->count(),
      'evaluation_end_date' => $evaluation->end_date,
      'days_remaining' => now() < $evaluation->end_date ? now()->diffInDays($evaluation->end_date) : 0,
      'is_active' => $evaluation->status == 1,
    ];

    // 7. MÉTRICAS POR ÁREA (Si hay múltiples áreas en el equipo)
    $areaMetrics = $teamResults->groupBy('area')->map(function ($areaGroup, $areaName) {
      return [
        'area' => $areaName ?? 'Sin área',
        'total' => $areaGroup->count(),
        'average_result' => round($areaGroup->where('result', '>', 0)->avg('result'), 2),
        'completed' => $areaGroup->where('is_completed', true)->count(),
      ];
    })->values();

    // 8. MÉTRICAS POR CATEGORÍA JERÁRQUICA
    $categoryMetrics = $teamResults->groupBy('hierarchical_category')->map(function ($categoryGroup, $categoryName) {
      return [
        'category' => $categoryName ?? 'Sin categoría',
        'total' => $categoryGroup->count(),
        'average_result' => round($categoryGroup->where('result', '>', 0)->avg('result'), 2),
        'completed' => $categoryGroup->where('is_completed', true)->count(),
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
        $hierarchicalCategory = HierarchicalCategory
          ::where('id', $category->hierarchical_category_id)
          ->with('workers') // Sin constraint, obtiene todos los workers
          ->first();

        // Verificar que existe y tiene workers
        if ($hierarchicalCategory && $hierarchicalCategory->workers->isNotEmpty()) {
          foreach ($hierarchicalCategory->workers as $person) {
            if ($person->fecha_inicio <= $cycle->cut_off_date) {
              $objectivesPercentage = $hierarchicalCategory->hasObjectives ? $evaluation->objectivesPercentage : 0;
              $competencesPercentage = $hierarchicalCategory->hasObjectives ? $evaluation->competencesPercentage : 100;

              $evaluator = $person->evaluator ?? $person->boss;

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
                'boss' => $person->boss?->nombre_completo,
                'boss_dni' => $person->boss?->vat,
                'boss_hierarchical_category' => $person->boss?->position?->hierarchicalCategory?->name ?? "-",
                'boss_position' => $person->boss?->position?->name,
                'boss_area' => $person->boss?->position?->area?->name,
                'boss_sede' => $person->boss?->sede?->abreviatura,
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

  public function regeneratePersonEvaluation(int $personId, int $evaluationId)
  {

    DB::transaction(function () use ($personId, $evaluationId) {

      $evaluation = Evaluation::findOrFail($evaluationId);
      // 1. Reset EvaluationPersonResult
      $personResult = EvaluationPersonResult::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->first();

      if ($personResult) {
        $personResult->update([
          'objectivesResult' => 0,
          'competencesResult' => 0,
          'status' => 0,
          'result' => 0,
          'comments' => null,
        ]);
      }

      // 2. Reset EvaluationPersonDashboard
      $dashboard = EvaluationPersonDashboard::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->first();

      if ($dashboard) {
        $dashboard->resetStats();
      }

      // 3. Delete EvaluationPersonCompetenceDetail records
      EvaluationPersonCompetenceDetail::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->delete();

      // 4. Reset EvaluationPerson if exists
      $evaluationsPerson = EvaluationPerson::where('person_id', $personId)
        ->where('evaluation_id', $evaluationId)
        ->get();

      if ($evaluationsPerson) {
        foreach ($evaluationsPerson as $evaluationPerson) {
          $evaluationPerson->update([
            'result' => 0,
            'compliance' => 0,
            'qualification' => 0,
            'comment' => null,
            'wasEvaluated' => false,
          ]);
        }
      }
    });

    return ['message' => 'Evaluación del   colaborador regenerada correctamente'];
  }
}
