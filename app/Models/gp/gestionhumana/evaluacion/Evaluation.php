<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use App\Jobs\UpdateEvaluationDashboards;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Evaluation extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'gh_evaluation';

  protected $fillable = [
    'name',
    'start_date',
    'end_date',
    'status',
    'selfEvaluation',
    'partnersEvaluation',
    'typeEvaluation',
    'objectivesPercentage',
    'competencesPercentage',
    'leadership_weight',
    'self_weight',
    'par_weight',
    'report_weight',
    'cycle_id',
    'period_id',
    'competence_parameter_id',
    'objective_parameter_id',
    'final_parameter_id',
    'send_opened_email',
    'send_closed_email',
  ];

  protected $casts = [
    'selfEvaluation' => 'boolean',
    'partnersEvaluation' => 'boolean',
    'send_opened_email' => 'boolean',
    'send_closed_email' => 'boolean',
    'objectivesPercentage' => 'decimal:2',
    'competencesPercentage' => 'decimal:2',
  ];

//  Evaluation Status
  const int PROGRAMMED_EVALUATION = 0;
  const int IN_PROGRESS_EVALUATION = 1;
  const int COMPLETED_EVALUATION = 2;

  const int EVALUATION_TYPE_OBJECTIVES = 0;
  const int EVALUATION_TYPE_180 = 1;
  const int EVALUATION_TYPE_360 = 2;

  // Configuración para reportes
  protected $reportColumns = [
    'id' => [
      'label' => 'ID',
      'formatter' => 'number',
      'width' => 8
    ],
    'name' => [
      'label' => 'Nombre de Evaluación',
      'formatter' => null,
      'width' => 30
    ],
    'tipo_evaluacion_texto' => [
      'label' => 'Tipo',
      'formatter' => null,
      'width' => 15
    ],
    'estado_texto' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 15
    ],
    'start_date' => [
      'label' => 'Fecha Inicio',
      'formatter' => 'date',
      'width' => 15
    ],
    'end_date' => [
      'label' => 'Fecha Fin',
      'formatter' => 'date',
      'width' => 15
    ],
    'selfEvaluation' => [
      'label' => 'Auto-evaluación',
      'formatter' => 'boolean',
      'width' => 15
    ],
    'partnersEvaluation' => [
      'label' => 'Eval. Pares',
      'formatter' => 'boolean',
      'width' => 15
    ],
    'objectivesPercentage' => [
      'label' => '% Objetivos',
      'formatter' => 'percentage',
      'width' => 12
    ],
    'competencesPercentage' => [
      'label' => '% Competencias',
      'formatter' => 'percentage',
      'width' => 15
    ],
    'cycle.name' => [
      'label' => 'Ciclo',
      'formatter' => null,
      'width' => 20
    ],
    'period.name' => [
      'label' => 'Período',
      'formatter' => null,
      'width' => 20
    ]
  ];

  protected $reportRelations = ['cycle', 'period', 'competenceParameter', 'objectiveParameter', 'finalParameter'];

  protected $reportStyles = [
    1 => [
      'font' => [
        'bold' => true,
        'size' => 12,
        'color' => ['rgb' => 'FFFFFF']
      ],
      'fill' => [
        'fillType' => 'solid',
        'startColor' => ['rgb' => '4472C4']
      ],
      'alignment' => [
        'horizontal' => 'center',
        'vertical' => 'center'
      ]
    ],
    'A2:L1000' => [
      'alignment' => [
        'vertical' => 'center'
      ],
      'borders' => [
        'allBorders' => [
          'borderStyle' => 'thin',
          'color' => ['rgb' => 'D4D4D4']
        ]
      ]
    ],
    'I:J' => [
      'alignment' => [
        'horizontal' => 'center'
      ]
    ],
    'E:F' => [
      'alignment' => [
        'horizontal' => 'center'
      ]
    ]
  ];

  // Relaciones
  public function cycle()
  {
    return $this->belongsTo(EvaluationCycle::class, 'cycle_id');
  }

  public function period()
  {
    return $this->belongsTo(EvaluationPeriod::class, 'period_id');
  }

  public function competenceParameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'competence_parameter_id');
  }

  public function objectiveParameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'objective_parameter_id');
  }

  public function finalParameter()
  {
    return $this->belongsTo(EvaluationParameter::class, 'final_parameter_id');
  }

  public function personResults()
  {
    return $this->hasMany(EvaluationPersonResult::class, 'evaluation_id');
  }

  public function competenceDetails()
  {
    return $this->hasMany(EvaluationPersonCompetenceDetail::class, 'evaluation_id');
  }

  public function dashboard()
  {
    return $this->hasOne(\App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard::class, 'evaluation_id');
  }

  // Métodos para reportes
  public function processReportData($data)
  {
    return $data->map(function ($item) {
      $item->tipo_evaluacion_texto = $this->getTipoEvaluacionTexto($item->typeEvaluation);
      $item->estado_texto = $this->getEstadoTexto($item->status);
      return $item;
    });
  }

  public function generateReportSummary($data)
  {
    $total = $data->count();
    $programadas = $data->where('status', 0)->count();
    $enProgreso = $data->where('status', 1)->count();
    $finalizadas = $data->where('status', 2)->count();

    return [
      'Programadas' => $programadas,
      'En Progreso' => $enProgreso,
      'Finalizadas' => $finalizadas,
      'Completadas' => $total > 0 ? round(($finalizadas / $total) * 100, 1) . '%' : '0%'
    ];
  }

  // Métodos auxiliares
  private function getTipoEvaluacionTexto($tipo)
  {
    $tipos = [0 => 'Objetivos', 1 => '180°', 2 => '360°'];
    return $tipos[$tipo] ?? 'Desconocido';
  }

  private function getEstadoTexto($status)
  {
    $estados = [0 => 'Programada', 1 => 'En Progreso', 2 => 'Finalizada'];
    return $estados[$status] ?? 'Desconocido';
  }

  // Accessors originales
  public function getTipoEvaluacionTextoAttribute()
  {
    return $this->getTipoEvaluacionTexto($this->typeEvaluation);
  }

  public function getEstadoTextoAttribute()
  {
    return $this->getEstadoTexto($this->status);
  }

  public function getMaxScoreCompetenceAttribute()
  {
    return $this->competenceParameter?->details()->max('to');
  }

  public function getMaxScoreObjectiveAttribute()
  {
    return $this->objectiveParameter?->details()->max('to');
  }

  public function getMaxScoreFinalAttribute()
  {
    return $this->finalParameter?->details()->max('to');
  }

  // Constantes originales
  const filters = [
    'search' => ['name'],
    'name' => 'like',
    'start_date' => '=',
    'end_date' => '=',
    'status' => '=',
    'typeEvaluation' => '=',
    'selfEvaluation' => '=',
    'partnersEvaluation' => '=',
    'objectivesPercentage' => '>=',
    'competencesPercentage' => '>=',
    'cycle_id' => '=',
    'period_id' => '=',
    'competence_parameter_id' => '=',
    'objective_parameter_id' => '=',
    'final_parameter_id' => '=',
  ];

  const sorts = [
    'id', 'name', 'start_date', 'end_date', 'status', 'typeEvaluation',
    'selfEvaluation', 'partnersEvaluation', 'objectivesPercentage',
    'competencesPercentage', 'cycle_id', 'period_id', 'competence_parameter_id',
    'objective_parameter_id', 'final_parameter_id', 'created_at', 'updated_at'
  ];

  /**
   * Obtiene estadísticas completas de progreso de la evaluación
   */
  public function getProgressStatsAttribute()
  {
    // Intentar obtener datos del dashboard primero
    $dashboard = $this->dashboard;
    if ($dashboard && $dashboard->last_calculated_at) {
      return [
        'total_participants' => $dashboard->total_participants,
        'completed_participants' => $dashboard->completed_participants,
        'in_progress_participants' => $dashboard->in_progress_participants,
        'not_started_participants' => $dashboard->not_started_participants,
        'completion_percentage' => $dashboard->completion_percentage,
        'progress_percentage' => $dashboard->progress_percentage,
      ];
    }

    // Fallback al cálculo original si no hay dashboard
    return $this->fallbackCalculateProgressStats();
  }

  public function getProgressStatsFallbackAttribute()
  {
    return $this->fallbackCalculateProgressStats();
  }

  /**
   * Obtiene solo la cantidad de resultados completados
   */
  public function getCompletedResultsCountAttribute()
  {
    return $this->personResults()
      ->get()
      ->filter(function ($result) {
        return $result->is_completed;
      })
      ->count();
  }

  /**
   * Obtiene el porcentaje de completitud de la evaluación
   */
  public function getCompletionPercentageAttribute()
  {
    $total = $this->personResults()->count();
    $completed = $this->completed_results_count;

    return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
  }

  /**
   * Verifica si la evaluación está completamente terminada por todos
   */
  public function getIsFullyCompletedAttribute()
  {
    $total = $this->personResults()->count();
    $completed = $this->completed_results_count;

    return $total > 0 && $total === $completed;
  }

  /**
   * Obtiene estadísticas detalladas por competencia
   */
  public function getCompetenceStatsAttribute()
  {
    // Intentar obtener datos del dashboard primero
    $dashboard = $this->dashboard;
    if ($dashboard && $dashboard->last_calculated_at && $dashboard->competence_stats) {
      return $dashboard->competence_stats;
    }

    // Fallback al cálculo original si no hay dashboard
    $personResults = $this->personResults()->with('competenceDetails')->get();

    $competenceStats = [];

    foreach ($personResults as $personResult) {
      $groupedCompetences = $personResult->getGroupedCompetences();

      foreach ($groupedCompetences as $competence) {
        $competenceId = $competence['competence_id'];

        if (!isset($competenceStats[$competenceId])) {
          $competenceStats[$competenceId] = [
            'competence_name' => $competence['competence_name'],
            'total_participants' => 0,
            'completed_participants' => 0,
            'average_score' => 0,
            'scores' => [],
          ];
        }

        $competenceStats[$competenceId]['total_participants']++;

        if ($competence['completed_evaluations'] === $competence['total_sub_competences']) {
          $competenceStats[$competenceId]['completed_participants']++;
        }

        if ($competence['average_result'] > 0) {
          $competenceStats[$competenceId]['scores'][] = $competence['average_result'];
        }
      }
    }

    // Calcular promedios finales
    foreach ($competenceStats as $competenceId => &$stats) {
      if (!empty($stats['scores'])) {
        $stats['average_score'] = round(array_sum($stats['scores']) / count($stats['scores']), 2);
      }
      $stats['completion_percentage'] = $stats['total_participants'] > 0 ?
        round(($stats['completed_participants'] / $stats['total_participants']) * 100, 2) : 0;

      unset($stats['scores']); // Remover scores individuales del resultado final
    }

    return array_values($competenceStats);
  }

  /**
   * Obtiene estadísticas por tipo de evaluador (solo para 360°)
   */
  public function getEvaluatorTypeStatsAttribute()
  {
    if ($this->typeEvaluation != 2) { // Solo para 360°
      return [];
    }

    // Intentar obtener datos del dashboard primero
    $dashboard = $this->dashboard;
    if ($dashboard && $dashboard->last_calculated_at && $dashboard->evaluator_type_stats) {
      return $dashboard->evaluator_type_stats;
    }

    // Fallback al cálculo original si no hay dashboard
    $evaluatorTypes = [
      0 => 'Jefe Directo',
      1 => 'Pares',
      2 => 'Subordinados',
      3 => 'Autoevaluación'
    ];

    $stats = [];

    foreach ($evaluatorTypes as $type => $name) {
      $totalEvaluations = 0;
      $completedEvaluations = 0;

      foreach ($this->personResults as $personResult) {
        $groupedCompetences = $personResult->getGroupedCompetences();

        foreach ($groupedCompetences as $competence) {
          foreach ($competence['sub_competences'] as $subCompetence) {
            $evaluator = collect($subCompetence['evaluators'])
              ->firstWhere('evaluator_type', $type);

            if ($evaluator) {
              $totalEvaluations++;
              if ($evaluator['is_completed']) {
                $completedEvaluations++;
              }
            }
          }
        }
      }

      if ($totalEvaluations > 0) {
        $stats[] = [
          'evaluator_type' => $type,
          'evaluator_type_name' => $name,
          'total_evaluations' => $totalEvaluations,
          'completed_evaluations' => $completedEvaluations,
          'completion_percentage' => round(($completedEvaluations / $totalEvaluations) * 100, 2),
        ];
      }
    }

    return $stats;
  }

  /**
   * Scope para evaluaciones con cierto porcentaje de completitud
   */
  public function scopeWithCompletionRate($query, $minPercentage = 0)
  {
    return $query->get()->filter(function ($evaluation) use ($minPercentage) {
      return $evaluation->completion_percentage >= $minPercentage;
    });
  }

  /**
   * Scope para evaluaciones completamente terminadas
   */
  public function scopeFullyCompleted($query)
  {
    return $query->get()->filter(function ($evaluation) {
      return $evaluation->is_fully_completed;
    });
  }

  /**
   * Obtiene el ranking de participantes por puntaje final
   */
  public function getParticipantRankingAttribute()
  {
    // Intentar obtener datos del dashboard primero
    $dashboard = $this->dashboard;
    if ($dashboard && $dashboard->last_calculated_at && $dashboard->participant_ranking) {
      return $dashboard->participant_ranking;
    }

    // Fallback al cálculo original si no hay dashboard
    return $this->personResults()
      ->with('person')
      ->get()
      ->sortByDesc('result')
      ->values()
      ->map(function ($result, $index) {
        return [
          'position' => $index + 1,
          'person_id' => $result->person_id,
          'person_name' => $result->person->nombre_completo ?? 'N/A',
          'final_score' => round($result->result, 2),
          'completion_percentage' => $result->completion_percentage,
          'is_completed' => $result->is_completed,
        ];
      })
      ->toArray();
  }

  /**
   * Obtiene un resumen ejecutivo de la evaluación
   */
  public function getExecutiveSummaryAttribute()
  {
    // Intentar obtener datos del dashboard primero
    $dashboard = $this->dashboard;
    if ($dashboard && $dashboard->last_calculated_at && $dashboard->executive_summary) {
      return $dashboard->executive_summary;
    }

    // Fallback al cálculo original si no hay dashboard
    $progressStats = $this->progress_stats;
    $competenceStats = $this->competence_stats;

    // Competencia con menor desempeño
    $lowestCompetence = collect($competenceStats)
      ->sortBy('average_score')
      ->first();

    // Competencia con mayor desempeño
    $highestCompetence = collect($competenceStats)
      ->sortByDesc('average_score')
      ->first();

    // Promedio general de la evaluación
    $averageFinalScore = $this->personResults()
      ->where('result', '>', 0)
      ->avg('result');

    return [
      'evaluation_name' => $this->name,
      'total_participants' => $progressStats['total_participants'],
      'completion_rate' => $progressStats['completion_percentage'],
      'average_final_score' => round($averageFinalScore, 2),
      'max_possible_score' => $this->max_score_final,
      'performance_percentage' => $this->max_score_final > 0 ?
        round(($averageFinalScore / $this->max_score_final) * 100, 2) : 0,
      'strongest_competence' => $highestCompetence,
      'weakest_competence' => $lowestCompetence,
      'status' => $this->estado_texto,
      'evaluation_period' => [
        'start' => $this->start_date,
        'end' => $this->end_date,
        'days_duration' => \Carbon\Carbon::parse($this->start_date)
          ->diffInDays(\Carbon\Carbon::parse($this->end_date)),
      ],
    ];
  }

  /**
   * Obtiene estadísticas de resultados agrupados por rangos del parámetro final
   */
  public function getResultsStatsAttribute()
  {
    // Intentar obtener datos del dashboard primero
    $dashboard = $this->dashboard;
    if ($dashboard && $dashboard->last_calculated_at && $dashboard->results_stats) {
      return $dashboard->results_stats;
    }

    // Fallback al cálculo original si no hay dashboard
    return $this->fallbackCalculateResultsStats();
  }

  /**
   * Calcula las estadísticas de resultados por rangos (fallback y para el job)
   */
  public function fallbackCalculateResultsStats()
  {
    if (!$this->final_parameter_id) {
      return [
        'ranges' => [],
        'total_evaluated' => 0,
        'message' => 'No hay parámetro final configurado'
      ];
    }

    $finalParameter = $this->finalParameter;
    if (!$finalParameter || !$finalParameter->details || $finalParameter->details->isEmpty()) {
      return [
        'ranges' => [],
        'total_evaluated' => 0,
        'message' => 'No hay rangos configurados en el parámetro final'
      ];
    }

    // Obtener rangos ordenados
    $ranges = $finalParameter->details()
      ->orderBy('from')
      ->get();

    // Usar SQL directo para contar resultados por rango (mucho más eficiente)
    // Solo contamos personas con result > 0 como "evaluados"
    $rangeStats = [];
    $totalEvaluated = 0;

    foreach ($ranges as $index => $range) {
      $isLastRange = $index === ($ranges->count() - 1);

      // Query SQL directa para contar en este rango
      $query = \DB::table('gh_evaluation_person_result')
        ->where('evaluation_id', $this->id)
        ->where('result', '>', 0)
        ->where('result', '>=', $range->from);

      if ($isLastRange) {
        $query->where('result', '<=', $range->to);
      } else {
        $query->where('result', '<', $range->to);
      }

      $countInRange = $query->count();
      $totalEvaluated += $countInRange;

      $rangeStats[] = [
        'range_index' => $index,
        'range_label' => $range->label,
        'range_from' => $range->from,
        'range_to' => $range->to,
        'count' => $countInRange,
        'percentage' => 0 // Se calcula después
      ];
    }

    // Calcular porcentajes
    foreach ($rangeStats as &$stat) {
      $stat['percentage'] = $totalEvaluated > 0
        ? round(($stat['count'] / $totalEvaluated) * 100, 2)
        : 0;
    }

    if ($totalEvaluated === 0) {
      return [
        'ranges' => [],
        'total_evaluated' => 0,
        'message' => 'No hay resultados evaluados'
      ];
    }

    return [
      'ranges' => $rangeStats,
      'total_evaluated' => $totalEvaluated,
      'parameter_name' => $finalParameter->name
    ];
  }

  /**
   * @param bool $dispatchJob Si debe disparar el job para crear dashboards individuales
   * @return array
   */
  public function fallbackCalculateProgressStats(bool $dispatchJob = true): array
  {
    // Primero intentar obtener datos de los dashboards pre-calculados (SQL directo)
    $dashboardStats = DB::table('evaluation_person_dashboards')
      ->where('evaluation_id', $this->id)
      ->whereNotNull('last_calculated_at')
      ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN completion_rate > 0 AND is_completed = 0 THEN 1 ELSE 0 END) as in_progress
      ')
      ->first();

    // Si hay dashboards calculados, usar esos datos
    if ($dashboardStats && $dashboardStats->total > 0) {
      $totalParticipants = $dashboardStats->total;
      $completedParticipants = (int)$dashboardStats->completed;
      $inProgressParticipants = (int)$dashboardStats->in_progress;
    } else {
      // Fallback optimizado: Calcular con queries SQL directas sin procesar cada persona
      $totalParticipants = $this->personResults()->count();

      if ($totalParticipants === 0) {
        $completedParticipants = 0;
        $inProgressParticipants = 0;
      } else {
        // Determinar completados basándose en el tipo de evaluación
        if ($this->typeEvaluation == 0) {
          // Evaluación de objetivos: completado si todos los objetivos están evaluados
          $completedParticipants = \DB::table('gh_evaluation_person_result as epr')
            ->where('epr.evaluation_id', $this->id)
            ->whereNotExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person as ep')
                ->whereRaw('ep.evaluation_id = epr.evaluation_id')
                ->whereRaw('ep.person_id = epr.person_id')
                ->where('ep.wasEvaluated', 0)
                ->whereNull('ep.deleted_at');
            })
            ->whereExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person as ep')
                ->whereRaw('ep.evaluation_id = epr.evaluation_id')
                ->whereRaw('ep.person_id = epr.person_id')
                ->whereNull('ep.deleted_at');
            })
            ->count();

          // En progreso: tiene al menos un objetivo evaluado pero no todos
          $inProgressParticipants = \DB::table('gh_evaluation_person_result as epr')
            ->where('epr.evaluation_id', $this->id)
            ->whereExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person as ep')
                ->whereRaw('ep.evaluation_id = epr.evaluation_id')
                ->whereRaw('ep.person_id = epr.person_id')
                ->where('ep.wasEvaluated', 1)
                ->whereNull('ep.deleted_at');
            })
            ->whereExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person as ep')
                ->whereRaw('ep.evaluation_id = epr.evaluation_id')
                ->whereRaw('ep.person_id = epr.person_id')
                ->where('ep.wasEvaluated', 0)
                ->whereNull('ep.deleted_at');
            })
            ->count();
        } else {
          // Evaluación 180° o 360°: basarse en competencias
          // Completado: todas las competencias tienen resultado > 0
          $completedParticipants = \DB::table('gh_evaluation_person_result as epr')
            ->where('epr.evaluation_id', $this->id)
            ->whereNotExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person_competence_detail as epcd')
                ->whereRaw('epcd.evaluation_id = epr.evaluation_id')
                ->whereRaw('epcd.person_id = epr.person_id')
                ->where('epcd.result', 0);
            })
            ->whereExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person_competence_detail as epcd')
                ->whereRaw('epcd.evaluation_id = epr.evaluation_id')
                ->whereRaw('epcd.person_id = epr.person_id');
            })
            ->count();

          // En progreso: tiene al menos una competencia con resultado pero no todas
          $inProgressParticipants = \DB::table('gh_evaluation_person_result as epr')
            ->where('epr.evaluation_id', $this->id)
            ->whereExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person_competence_detail as epcd')
                ->whereRaw('epcd.evaluation_id = epr.evaluation_id')
                ->whereRaw('epcd.person_id = epr.person_id')
                ->where('epcd.result', '>', 0);
            })
            ->whereExists(function ($query) {
              $query->select(\DB::raw(1))
                ->from('gh_evaluation_person_competence_detail as epcd')
                ->whereRaw('epcd.evaluation_id = epr.evaluation_id')
                ->whereRaw('epcd.person_id = epr.person_id')
                ->where('epcd.result', 0);
            })
            ->count();
        }
      }
    }

    $notStartedParticipants = $totalParticipants - $completedParticipants - $inProgressParticipants;

    $stats = [
      'total_participants' => $totalParticipants,
      'completed_participants' => $completedParticipants,
      'in_progress_participants' => $inProgressParticipants,
      'not_started_participants' => $notStartedParticipants,
      'completion_percentage' => $totalParticipants > 0 ?
        round(($completedParticipants / $totalParticipants) * 100, 2) : 0,
      'progress_percentage' => $totalParticipants > 0 ?
        round((($completedParticipants + $inProgressParticipants) / $totalParticipants) * 100, 2) : 0,
    ];

    // Crear o actualizar el dashboard global con los datos calculados
    $this->dashboard()->updateOrCreate(
      ['evaluation_id' => $this->id],
      [
        'total_participants' => $stats['total_participants'],
        'completed_participants' => $stats['completed_participants'],
        'in_progress_participants' => $stats['in_progress_participants'],
        'not_started_participants' => $stats['not_started_participants'],
        'completion_percentage' => $stats['completion_percentage'],
        'progress_percentage' => $stats['progress_percentage'],
        'last_calculated_at' => now(),
      ]
    );

    // Si no había dashboards individuales, disparar job para crearlos en background
    if ($dispatchJob && (!isset($dashboardStats) || !$dashboardStats || $dashboardStats->total == 0)) {
      UpdateEvaluationDashboards::dispatch($this->id)
        ->onQueue('evaluation-dashboards');
    }

    return $stats;
  }
}
