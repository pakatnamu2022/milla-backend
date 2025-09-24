<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPersonResult extends BaseModel
{
  use SoftDeletes, Reportable;

  protected $table = 'gh_evaluation_person_result';

  // Constantes para estados de progreso
  const PROGRESS_NOT_STARTED = 'sin_iniciar';
  const PROGRESS_IN_PROGRESS = 'en_proceso';
  const PROGRESS_COMPLETED = 'completado';

  protected $fillable = [
    'person_id',
    'evaluation_id',
    'competencesPercentage',
    'objectivesPercentage',
    'objectivesResult',
    'competencesResult',
    'status',
    'result',
    'name',
    'dni',
    'hierarchical_category',
    'position',
    'area',
    'sede',
    'boss',
    'boss_dni',
    'boss_hierarchical_category',
    'boss_position',
    'boss_area',
    'boss_sede',
    'comments',
  ];

  const filters = [
    'search' => ['person.nombre_completo'],
    'person_id' => '=',
    'person.cargo_id' => '=',
    'evaluation_id' => '=',
    'competencesPercentage' => '=',
    'objectivesPercentage' => '=',
    'objectivesResult' => '=',
    'competencesResult' => '=',
    'result' => '=',

    // 👇 NUEVOS: Filtros por accessor
    'is_completed' => 'accessor_bool',
    'is_in_progress' => 'accessor_bool',
    'completion_percentage' => 'accessor_numeric',
    'total_progress.completion_rate' => 'accessor_numeric',
    'objectives_progress.is_completed' => 'accessor_bool',
    'competences_progress.is_completed' => 'accessor_bool',
    'progress_status' => 'accessor_string', // completado, en_proceso, sin_iniciar

    // Rangos numéricos
    'completion_percentage_range' => 'accessor_between', // [min, max]
    'result_range' => 'between', // Para columna real de BD
  ];

  const sorts = [
    'id' => 'asc',
    'person_id',
    'evaluation_id',
    'competencesPercentage',
    'objectivesPercentage',
    'objectivesResult',
    'competencesResult',
    'result',
  ];

  public function person()
  {
    return $this->belongsTo(Person::class, 'person_id');
  }

  public function evaluation()
  {
    return $this->belongsTo(Evaluation::class, 'evaluation_id');
  }

  public function details()
  {
    return $this->hasMany(EvaluationPerson::class, 'evaluation_id', 'evaluation_id')
      ->where('person_id', $this->person_id);
  }

  public function competenceDetails()
  {
    return $this->hasMany(EvaluationPersonCompetenceDetail::class, 'evaluation_id', 'evaluation_id')
      ->where('person_id', $this->person_id);
  }

  // ← CONFIGURACIÓN DEL REPORTE CON FORMATO SOLICITADO
  protected $reportColumns = [
    'nombres' => [
      'label' => 'Nombres',
      'formatter' => null,
      'width' => 15,
      'accessor' => 'getNombresAttribute'
    ],
    'apellidos' => [
      'label' => 'Apellidos',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getApellidosAttribute'
    ],
    'person.vat' => [
      'label' => 'DNI',
      'formatter' => null,
      'width' => 12
    ],
    'person.position.name' => [
      'label' => 'Puesto',
      'formatter' => null,
      'width' => 20
    ],
    'person.position.area.name' => [
      'label' => 'Área',
      'formatter' => null,
      'width' => 20
    ],
    'person.position.hierarchicalCategory.name' => [
      'label' => 'Categoría jerárquica',
      'formatter' => null,
      'width' => 25
    ],
    'person.sede.abreviatura' => [
      'label' => 'Sede',
      'formatter' => null,
      'width' => 15
    ],
    'evaluaciones_formato' => [
      'label' => 'Evaluaciones',
      'formatter' => null,
      'width' => 12,
      'accessor' => 'getEvaluacionesFormatoAttribute'
    ],
    'progreso_porcentaje' => [
      'label' => 'Progreso',
      'formatter' => null,
      'width' => 10,
      'accessor' => 'getProgresoPorcentajeAttribute'
    ],
    'finalizacion_estado' => [
      'label' => 'Finalización',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getFinalizacionEstadoAttribute'
    ],
    'estado_evaluacion' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 18,
      'accessor' => 'getEstadoEvaluacionAttribute'
    ],
    'reunion_feedback' => [
      'label' => 'Reunión feedback',
      'formatter' => null,
      'width' => 15,
      'accessor' => 'getReunionFeedbackAttribute'
    ],
    'nota_feedback_subordinado' => [
      'label' => 'Nota feedback (SUBORDINADO)',
      'formatter' => null,
      'width' => 25,
      'accessor' => 'getNotaFeedbackSubordinadoAttribute'
    ]
  ];

  protected $reportRelations = [
    'person.position.area',
    'person.position.hierarchicalCategory',
    'person.sede',
    'person.subordinates',
    'evaluation'
  ];

  /**
   * Accessor para obtener solo los nombres
   */
  public function getNombresAttribute()
  {
    $nombreCompleto = explode(' ', $this->person->nombre_completo);
    return implode(' ', array_slice($nombreCompleto, 0, 2)); // Primeros 2 elementos
  }

  /**
   * Accessor para obtener solo los apellidos
   */
  public function getApellidosAttribute()
  {
    $nombreCompleto = explode(' ', $this->person->nombre_completo);
    return implode(' ', array_slice($nombreCompleto, 2)); // Resto de elementos
  }

  /**
   * Accessor para formato de evaluaciones (completadas/total)
   */
  public function getEvaluacionesFormatoAttribute()
  {
    $totalProgress = $this->total_progress;
    return "{$totalProgress['completed_sections']}/{$totalProgress['total_sections']}";
  }

  /**
   * Accessor para progreso en porcentaje
   */
  public function getProgresoPorcentajeAttribute()
  {
    return number_format($this->total_progress['completion_rate'] * 100, 2) . '%';
  }

  /**
   * Accessor para estado de finalización
   */
  public function getFinalizacionEstadoAttribute()
  {
    return $this->total_progress['is_completed'] ? 'Completado' : 'Evaluaciones pendientes';
  }

  /**
   * Accessor para estado general de evaluación
   */
  public function getEstadoEvaluacionAttribute()
  {
    $totalProgress = $this->total_progress;

    if ($totalProgress['is_completed']) {
      return 'Completado';
    } elseif ($totalProgress['completion_rate'] > 0) {
      return 'Evaluación en proceso';
    } else {
      return 'Pendiente';
    }
  }

  /**
   * Accessor para reunión feedback con subordinados
   */
  public function getReunionFeedbackAttribute()
  {
    $subordinadosTotal = 0;
    $subordinadosCompletados = 0;

    if ($this->person->subordinates()->exists()) {
      $subordinadosTotal = $this->person->subordinates()->count();
      // Aquí podrías agregar lógica específica para contar subordinados que completaron feedback
      $subordinadosCompletados = 0; // Placeholder
    }

    return $subordinadosTotal > 0 ? "{$subordinadosCompletados}/{$subordinadosTotal}" : "0/0";
  }

  /**
   * Accessor para nota de feedback de subordinados
   */
  public function getNotaFeedbackSubordinadoAttribute()
  {
    // Placeholder - aquí podrías implementar lógica específica para obtener la nota
    return 'Pendiente';
  }

  /**
   * Calcula el progreso total de la evaluación (objetivos + competencias)
   */
  public function getTotalProgressAttribute()
  {
    $objectivesProgress = $this->getObjectivesProgressAttribute();
    $competencesProgress = $this->getCompetencesProgressAttribute();
    $objectivesPercentage = max($this->objectivesPercentage, 1);
    $competencesPercentage = max($this->competencesPercentage, 1);

    $totalSections = 0;
    $completedSections = 0;

    // Solo contar objetivos si la persona los tiene
    if ($this->person->position->hierarchicalCategory->hasObjectives) {
      $totalSections++;
      if ($objectivesProgress['completion_rate'] == 100) {
        $completedSections++;
      }
    }

    // Solo contar competencias si la evaluación no es de objetivos
    if ($this->evaluation->typeEvaluation != 0) {
      $totalSections++;
      if ($competencesProgress['completion_rate'] == 100) {
        $completedSections++;
      }
    }

    return [
      'completion_rate' => round(($objectivesProgress['completion_rate'] / $objectivesPercentage) + ($competencesPercentage > 0 ? ($competencesProgress['completion_rate'] / $competencesPercentage) : 0), 2),
      'completed_sections' => $completedSections,
      'total_sections' => $totalSections,
      'is_completed' => $completedSections === $totalSections,
      'objectives_progress' => $objectivesProgress,
      'competences_progress' => $competencesProgress,
    ];
  }

  /**
   * Calcula el progreso de los objetivos
   */
  public function getObjectivesProgressAttribute()
  {
    $objectives = $this->details;
    $totalObjectives = $objectives->count();
    $completedObjectives = $objectives->where('wasEvaluated', 1)->count();

    $completionRate = $totalObjectives > 0 ?
      round(($completedObjectives / $totalObjectives) * 100, 2) : 0;

    return [
      'completion_rate' => $completionRate,
      'completed' => $completedObjectives,
      'total' => $totalObjectives,
      'is_completed' => $completionRate == 100,
      'has_objectives' => (bool)$this->person->position->hierarchicalCategory->hasObjectives,
    ];
  }

  /**
   * Calcula el progreso de las competencias
   */
  public function getCompetencesProgressAttribute()
  {
    $competenceGroups = $this->getGroupedCompetences();

    $totalSubCompetences = collect($competenceGroups)->sum('total_sub_competences');
    $completedSubCompetences = collect($competenceGroups)->sum('completed_evaluations');

    $completionRate = $totalSubCompetences > 0 ?
      round(($completedSubCompetences / $totalSubCompetences) * 100, 2) : 0;

    return [
      'completion_rate' => $completionRate,
      'completed' => $completedSubCompetences,
      'total' => $totalSubCompetences,
      'is_completed' => $completionRate == $totalSubCompetences,
      'groups' => count($competenceGroups),
    ];
  }

  /**
   * Verifica si la evaluación está completamente terminada
   */
  public function getIsCompletedAttribute()
  {
    return $this->total_progress['is_completed'];
  }

  /**
   * Verifica si la evaluación está en progreso
   */
  public function getIsInProgressAttribute()
  {
    $totalProgress = $this->total_progress;
    return !$totalProgress['is_completed'] && $totalProgress['completion_rate'] > 0;
  }

  /**
   * Obtiene el porcentaje de progreso general
   */
  public function getCompletionPercentageAttribute()
  {
    return $this->total_progress['completion_rate'];
  }

  /**
   * Obtiene el estado de progreso en texto
   */
  public function getProgressStatusAttribute()
  {
    $totalProgress = $this->total_progress;

    if ($totalProgress['is_completed']) {
      return self::PROGRESS_COMPLETED;
    } elseif ($totalProgress['completion_rate'] > 0) {
      return self::PROGRESS_IN_PROGRESS;
    } else {
      return self::PROGRESS_NOT_STARTED;
    }
  }

  /**
   * Agrupa las competencias (método movido del Resource al Model)
   */
  public function getGroupedCompetences()
  {
    $groupedCompetences = [];
    $evaluationType = $this->evaluation->typeEvaluation;

    $mainCompetences = $this->competenceDetails
      ->groupBy(function ($item) {
        return $item->competence_id;
      });

    foreach ($mainCompetences as $competenceId => $competenceDetails) {
      $firstDetail = $competenceDetails->first();

      $subCompetencesByEvaluator = $competenceDetails->groupBy('sub_competence_id');
      $processedSubCompetences = [];

      foreach ($subCompetencesByEvaluator as $subCompetenceId => $evaluations) {
        $firstEvaluation = $evaluations->first();
        $evaluationsByType = $evaluations->groupBy('evaluatorType');

        $evaluators = [];
        $totalScore = 0;
        $validEvaluations = 0;

        $requiredEvaluatorTypes = $this->getRequiredEvaluatorTypes($evaluationType);

        foreach ($requiredEvaluatorTypes as $evaluatorType) {
          $evaluation = $evaluationsByType->get($evaluatorType)?->first();

          $evaluators[] = [
            'evaluator_type' => $evaluatorType,
            'evaluator_type_name' => $this->getEvaluatorTypeName($evaluatorType),
            'evaluator_id' => $evaluation?->evaluator_id,
            'evaluator_name' => $evaluation?->evaluator ?? 'Pendiente',
            'result' => $evaluation?->result ?? '0.00',
            'id' => $evaluation?->id,
            'is_completed' => $evaluation && floatval($evaluation->result) > 0,
          ];

          if ($evaluation && floatval($evaluation->result) > 0) {
            $totalScore += floatval($evaluation->result);
            $validEvaluations++;
          }
        }

        $averageScore = $validEvaluations > 0 ? $totalScore / $validEvaluations : 0;

        $processedSubCompetences[] = [
          'sub_competence_id' => $subCompetenceId,
          'sub_competence_name' => $firstEvaluation->sub_competence,
          'evaluators' => $evaluators,
          'average_result' => round($averageScore, 2),
          'completion_percentage' => ($validEvaluations / count($requiredEvaluatorTypes)) * 100,
          'is_completed' => $validEvaluations === count($requiredEvaluatorTypes),
        ];
      }

      $competenceAverage = collect($processedSubCompetences)->avg('average_result');
      $completedSubCompetences = collect($processedSubCompetences)->where('is_completed', true)->count();

      $groupedCompetences[] = [
        'competence_id' => $competenceId,
        'competence_name' => $firstDetail->competence,
        'sub_competences' => $processedSubCompetences,
        'average_result' => round($competenceAverage, 2),
        'total_sub_competences' => count($processedSubCompetences),
        'completed_evaluations' => $completedSubCompetences,
        'evaluation_type' => $evaluationType,
        'required_evaluator_types' => $this->getRequiredEvaluatorTypes($evaluationType),
      ];
    }

    return $groupedCompetences;
  }

  /**
   * Obtiene los tipos de evaluador requeridos según el tipo de evaluación
   */
  private function getRequiredEvaluatorTypes($evaluationType)
  {
    if ($evaluationType == 1) { // 180°
      return [0]; // Solo jefe directo
    } else { // 360°
      $types = [0, 3]; // Jefe directo + Autoevaluación
      $types[] = 1; // Pares

      if ($this->hasSubordinates()) {
        $types[] = 2; // Subordinados
      }

      return $types;
    }
  }

  /**
   * Verifica si la persona tiene subordinados
   */
  private function hasSubordinates()
  {
    return $this->person->subordinates()->exists();
  }

  /**
   * Obtiene el nombre del tipo de evaluador
   */
  private function getEvaluatorTypeName($type)
  {
    $types = [
      0 => 'Jefe Directo',
      1 => 'Par',
      2 => 'Subordinado',
      3 => 'Autoevaluación',
    ];

    return $types[$type] ?? 'Otro';
  }

  /**
   * Scope para filtrar evaluaciones completadas
   */
  public function scopeCompleted($query)
  {
    return $query->whereHas('details', function ($q) {
      $q->where('wasEvaluated', 1);
    })->whereHas('competenceDetails', function ($q) {
      $q->where('result', '>', 0);
    });
  }

  /**
   * Scope para filtrar evaluaciones incompletas
   */
  public function scopeIncomplete($query)
  {
    return $query->where(function ($q) {
      $q->whereHas('details', function ($subQ) {
        $subQ->where('wasEvaluated', 0);
      })->orWhereHas('competenceDetails', function ($subQ) {
        $subQ->where('result', 0);
      });
    });
  }

  /**
   * Scope para evaluaciones con progreso específico
   */
  public function scopeWithProgress($query, $minPercentage = 0)
  {
    return $query->get()->filter(function ($evaluation) use ($minPercentage) {
      return $evaluation->completion_percentage >= $minPercentage;
    });
  }

  /**
   * Scope para evaluaciones completadas
   */
  public function scopeCompletedProgress($query)
  {
    return $query->get()->filter(function ($evaluation) {
      return $evaluation->progress_status === 'completado';
    });
  }

  /**
   * Scope para evaluaciones en proceso
   */
  public function scopeInProgress($query)
  {
    return $query->get()->filter(function ($evaluation) {
      return $evaluation->progress_status === 'en_proceso';
    });
  }

  /**
   * Scope para evaluaciones sin iniciar
   */
  public function scopeNotStarted($query)
  {
    return $query->get()->filter(function ($evaluation) {
      return $evaluation->progress_status === 'sin_iniciar';
    });
  }

  /**
   * Scope para filtrar por estado de progreso
   */
  public function scopeByProgressStatus($query, $status)
  {
    return $query->get()->filter(function ($evaluation) use ($status) {
      return $evaluation->progress_status === $status;
    });
  }

}
