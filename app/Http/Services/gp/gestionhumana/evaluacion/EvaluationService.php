<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Jobs\UpdateEvaluationDashboards;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationModel;
use App\Models\gp\gestionhumana\evaluacion\EvaluationParEvaluator;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluationService extends BaseService
{
  protected EvaluationPersonService $evaluationPersonService;
  protected EvaluationPersonResultService $evaluationPersonResultService;
  protected $exportService;

  // Tipos de evaluador
  const TIPO_EVALUADOR_JEFE = 0;           // Líder directo
  const TIPO_EVALUADOR_AUTOEVALUACION = 1; // Autoevaluación
  const TIPO_EVALUADOR_COMPANEROS = 2;     // Compañeros
  const TIPO_EVALUADOR_REPORTES = 3;       // Reportes/Subordinados

  // Tipos de evaluación
  const EVALUACION_OBJETIVOS = 0;
  const EVALUACION_180 = 1;
  const EVALUACION_360 = 2;

  public function __construct()
  {
    $this->evaluationPersonService = new EvaluationPersonService();
    $this->evaluationPersonResultService = new EvaluationPersonResultService();
    $this->exportService = new ExportService();
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, Evaluation::class);
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Evaluation::class,
      $request,
      Evaluation::filters,
      Evaluation::sorts,
      EvaluationResource::class,
    );
  }

  public function active()
  {
    $activeEvaluation = Evaluation::where('status', 1)->first();
    if (!$activeEvaluation) {
      throw new Exception('No hay una evaluación activa en este momento.');
    }
    return (new EvaluationResource($activeEvaluation))->showExtra();
  }

  public function checkActiveEvaluationByDateRange(string $startDate, string $endDate)
  {
    $activeEvaluations = Evaluation::where(function ($query) use ($startDate, $endDate) {
      $query->whereBetween('start_date', [$startDate, $endDate])
        ->orWhereBetween('end_date', [$startDate, $endDate])
        ->orWhere(function ($query) use ($startDate, $endDate) {
          $query->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $endDate);
        });
    })->exists();

    if ($activeEvaluations) {
      return [
        'isValid' => false,
        'message' => 'Ya existe una evaluación activa que cruza con el rango de fechas proporcionado.'
      ];
    }

    return [
      'isValid' => true,
      'message' => 'No existen evaluaciones activas en el rango de fechas indicado.'
    ];
  }

  public function participants(int $id)
  {
    $evaluation = $this->find($id);
    $personsInCycle = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->select('person_id')
      ->distinct()
      ->get()
      ->pluck('person_id')
      ->toArray();
    $persons = Worker::whereIn('id', $personsInCycle)->get();
    return WorkerResource::collection($persons);
  }

  public function positions(int $id)
  {
    $evaluation = $this->find($id);
    $personsInCycle = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->select('person_id')
      ->distinct()
      ->get()
      ->pluck('person_id')
      ->toArray();
    $positionsIds = Worker::whereIn('id', $personsInCycle)->select('cargo_id')->distinct()->get()->pluck('cargo_id')->toArray();
    $positions = Position::whereIn('id', $positionsIds)->get();
    return PositionResource::collection($positions);
  }

  public function enrichData($data, $evaluation = null)
  {
    if (isset($data['cycle_id'])) {
      $cycle = EvaluationCycle::find($data['cycle_id']);
      $data['typeEvaluation'] = $cycle->typeEvaluation;
      $data['objective_parameter_id'] = $cycle->parameter_id;
      $data['period_id'] = $cycle->period_id;
    }
    if (isset($data['typeEvaluation']) && $data['typeEvaluation'] == self::EVALUACION_360) {
      $data['selfEvaluation'] = 1;
      $data['partnersEvaluation'] = 1;
    }

    if (!$evaluation) {
      $evaluationActive = Evaluation::where('status', Evaluation::IN_PROGRESS_EVALUATION)->first();
      if ($evaluationActive) {
        $data['status'] = Evaluation::PROGRAMMED_EVALUATION;
      } else {
        $data['status'] = Evaluation::IN_PROGRESS_EVALUATION;
      }
    }

    return $data;
  }

  public function find($id)
  {
    $evaluationCompetence = Evaluation::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store($data)
  {
    DB::beginTransaction();

    try {
      $data = $this->enrichData($data);
      $evaluation = Evaluation::create($data);

      // Crear personas y resultados
      $this->evaluationPersonResultService->storeMany($evaluation->id);
      $this->evaluationPersonService->storeMany($evaluation->id);

      // Si es evaluación 180° o 360°, crear competencias automáticamente
      if (in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        $competencesResult = $this->crearCompetenciasEvaluacion($evaluation);

        if (!$competencesResult['success']) {
          //         Log del error pero no fallar la creación de la evaluación
          Log::warning('Error al crear competencias automáticamente', [
            'evaluation_id' => $evaluation->id,
            'error' => $competencesResult['message']
          ]);
        }
      }

      DB::commit();
      return new EvaluationResource($evaluation);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Crear competencias para una evaluación 180° o 360°
   */
  private function crearCompetenciasEvaluacion($evaluacion)
  {
    try {
      // Obtener todas las personas que participarán en esta evaluación
      $personasResultado = EvaluationPersonResult::where('evaluation_id', $evaluacion->id)->get();

      if ($personasResultado->isEmpty()) {
        return [
          'success' => false,
          'message' => 'No se encontraron personas para esta evaluación'
        ];
      }

      // Limpiar competencias existentes para esta evaluación
      EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluacion->id)->delete();

      $totalPersonasProcesadas = 0;
      $totalCompetenciasCreadas = 0;

      foreach ($personasResultado as $personaResultado) {
        $persona = Worker::find($personaResultado->person_id);

        if (!$persona) {
          continue;
        }

        if ($evaluacion->typeEvaluation == self::EVALUACION_180) {
          $competenciasCreadas = $this->procesarEvaluacion180($evaluacion, $persona);
        } else {
          $competenciasCreadas = $this->procesarEvaluacion360($evaluacion, $persona);
        }

        $totalCompetenciasCreadas += $competenciasCreadas;
        $totalPersonasProcesadas++;
      }

      return [
        'success' => true,
        'message' => 'Competencias creadas exitosamente',
        'personas_procesadas' => $totalPersonasProcesadas,
        'competencias_creadas' => $totalCompetenciasCreadas
      ];
    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Error al crear competencias: ' . $e->getMessage()
      ];
    }
  }

  /**
   * Procesar evaluación 180°
   */
  private function procesarEvaluacion180($evaluacion, $persona)
  {
    $competenciasCreadas = 0;

    // Obtener competencias y subcompetencias según tu lógica de categorías
    $competenciasData = $this->obtenerCompetenciasParaPersona($persona);

    foreach ($competenciasData as $competenciaData) {
      // Solo crear para el líder directo en evaluación 180°
      if ($persona->jefe_id) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->jefe_id,
          self::TIPO_EVALUADOR_JEFE
        );
        $competenciasCreadas++;
      }
    }

    return $competenciasCreadas;
  }

  /**
   * Procesar evaluación 360°
   */
  private function procesarEvaluacion360($evaluacion, $persona)
  {
    $competenciasCreadas = 0;

    // Obtener el modelo de evaluación para la categoría jerárquica de la persona
    if (!$persona->position || !$persona->position->hierarchicalCategory) {
      return $competenciasCreadas;
    }

    $categoryId = $persona->position->hierarchicalCategory->id;
    $evaluationModel = EvaluationModel::getModelByCategory($categoryId);

    // Si no hay modelo de evaluación para esta categoría, no crear competencias
    if (!$evaluationModel) {
      return $competenciasCreadas;
    }

    // Determinar la estructura jerárquica de la persona
    $tieneJefe = !is_null($persona->jefe_id);
    $tieneSubordinados = $this->tieneSubordinados($persona->id);

    // Obtener competencias según tu estructura de categorías jerárquicas
    $competenciasData = $this->obtenerCompetenciasParaPersona($persona);

    foreach ($competenciasData as $competenciaData) {
      // 1. Autoevaluación (solo si el peso es mayor a 0)
      if ($evaluacion->selfEvaluation && $evaluationModel->self_weight > 0) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->id,
          self::TIPO_EVALUADOR_AUTOEVALUACION
        );
        $competenciasCreadas++;
      }

      // 2. Evaluación del jefe directo (solo si el peso es mayor a 0)
      if ($tieneJefe && $evaluationModel->leadership_weight > 0) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->jefe_id,
          self::TIPO_EVALUADOR_JEFE
        );
        $competenciasCreadas++;
      }

      // 3. Evaluación de compañeros (solo si está habilitada y el peso es mayor a 0)
      if ($evaluacion->partnersEvaluation && $evaluationModel->par_weight > 0) {
        $partners = $this->obtenerCompanerosPorEvaluationParEvaluator($persona);
        foreach ($partners as $partner) {
          $this->crearDetalleCompetencia(
            $evaluacion->id,
            $persona,
            $competenciaData,
            $partner->id,
            self::TIPO_EVALUADOR_COMPANEROS
          );
          $competenciasCreadas++;
        }
      }

      // 4. Evaluación de reportes directos (solo si el peso es mayor a 0 y tiene subordinados)
      if ($tieneSubordinados && $evaluationModel->report_weight > 0) {
        $subordinados = $this->obtenerSubordinados($persona->id);
        foreach ($subordinados as $subordinado) {
          $this->crearDetalleCompetencia(
            $evaluacion->id,
            $persona,
            $competenciaData,
            $subordinado->id,
            self::TIPO_EVALUADOR_REPORTES
          );
          $competenciasCreadas++;
        }
      }
    }

    return $competenciasCreadas;
  }

  /**
   * Crear detalle de competencia usando tu estructura real
   */
  private function crearDetalleCompetencia($evaluacionId, $persona, $competenciaData, $evaluadorId, $tipoEvaluador)
  {
    $evaluador = Worker::find($evaluadorId);

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
    // Usando tu lógica de EvaluationCategoryCompetenceDetail
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
   * Verificar si una persona tiene subordinados
   */
  private function tieneSubordinados($personaId)
  {
    return Worker::where('jefe_id', $personaId)
      ->where('status_deleted', 1)
      ->where('status_id', 22) // Activo según tu constante WORKER_ACTIVE
      ->exists();
  }

  /**
   * Obtener subordinados directos de una persona
   */
  private function obtenerSubordinados($personaId)
  {
    return Worker::where('jefe_id', $personaId)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->get();
  }

  /**
   * Obtener compañeros desde EvaluationParEvaluator
   */
  private function obtenerCompanerosPorEvaluationParEvaluator($persona)
  {
    $parEvaluators = EvaluationParEvaluator::where('worker_id', $persona->id)->get();

    if ($parEvaluators->isEmpty()) {
      return collect();
    }

    $mateIds = $parEvaluators->pluck('mate_id')->toArray();

    return Worker::whereIn('id', $mateIds)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->get();
  }

  /**
   * Obtener compañeros (personas con el mismo jefe, excluyendo a la persona actual)
   */
  private function obtenerCompaneros($persona)
  {
    if (!$persona->jefe_id) {
      return collect();
    }

    return Worker::where('jefe_id', $persona->jefe_id)
      ->where('id', '!=', $persona->id)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->get();
  }

  public function show(bool $show_extra, $id)
  {
    return (new EvaluationResource($this->find($id)))->showExtra($show_extra);
  }

  /**
   * Preview de qué pasará al regenerar toda la evaluación
   * SIN hacer cambios reales en la base de datos
   *
   * @param int $evaluationId
   * @param array $params
   * @return array
   */
  public function previewRegenerateEvaluation($evaluationId, array $params = [])
  {
    $evaluation = $this->find($evaluationId);
    $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);

    // Parámetros por defecto
    $mode = $params['mode'] ?? 'sync_with_cycle';
    $resetProgress = $params['reset_progress'] ?? false;
    $force = $params['force'] ?? false;

    // Verificar si hay cambios en las personas del ciclo
    $cycleChanges = $this->checkCycleChanges($evaluation);
    $needsRegeneration = $force || $cycleChanges;

    // Personas actualmente en la evaluación
    $currentPersons = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->pluck('person_id')
      ->toArray();

    // Personas que deberían estar según el ciclo actual
    $expectedPersons = $this->getPersonsFromCycle($evaluation->cycle_id);

    // Calcular diferencias
    $personsToAdd = array_diff($expectedPersons, $currentPersons);
    $personsToRemove = array_diff($currentPersons, $expectedPersons);

    // Obtener información COMPLETA y DETALLADA de las personas
    $currentPersonsDetails = $this->getDetailedPersonsInfo($currentPersons, $evaluation, $cycle, 'current');
    $personsToAddDetails = $this->getDetailedPersonsInfo($personsToAdd, $evaluation, $cycle, 'to_add');
    $personsToRemoveDetails = $this->getDetailedPersonsInfo($personsToRemove, $evaluation, $cycle, 'to_remove');

    // Contar registros actuales
    $currentStats = [
      'person_results' => EvaluationPersonResult::where('evaluation_id', $evaluation->id)->count(),
      'competence_details' => EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluation->id)->count(),
      'evaluation_persons' => EvaluationPerson::where('evaluation_id', $evaluation->id)->count(),
      'dashboards' => EvaluationPersonDashboard::where('evaluation_id', $evaluation->id)->count(),
    ];

    $result = [
      'evaluation' => [
        'id' => $evaluation->id,
        'name' => $evaluation->name,
        'cycle_id' => $evaluation->cycle_id,
        'type' => $evaluation->typeEvaluation,
      ],
      'parameters' => [
        'mode' => $mode,
        'reset_progress' => $resetProgress,
        'force' => $force,
      ],
      'current_state' => [
        'total_persons' => count($currentPersons),
        'statistics' => $currentStats,
      ],
      'cycle_analysis' => [
        'expected_persons_in_cycle' => count($expectedPersons),
        'changes_detected' => !empty($personsToAdd) || !empty($personsToRemove),
        'persons_to_add_count' => count($personsToAdd),
        'persons_to_remove_count' => count($personsToRemove),
      ],
      'validations' => [],
      'warnings' => [],
      'errors' => [],
    ];

    // Validar según el modo
    if (!$needsRegeneration && $mode !== 'full_reset') {
      $result['validations'][] = '✓ No se detectaron cambios en el ciclo';
      $result['warnings'][] = 'No se realizarán cambios a menos que uses force=true o mode=full_reset';
      $result['will_execute'] = false;
      $result['what_will_happen'] = 'No se realizarán cambios';
      return $result;
    }

    $result['will_execute'] = true;

    // Preview según el modo con información DETALLADA
    switch ($mode) {
      case 'full_reset':
        $result['affected_persons'] = $this->previewFullResetDetailed(
          $evaluation,
          $cycle,
          $currentPersonsDetails,
          $expectedPersons
        );
        break;

      case 'sync_with_cycle':
        $result['affected_persons'] = $this->previewSyncWithCycleDetailed(
          $evaluation,
          $cycle,
          $currentPersonsDetails,
          $personsToAddDetails,
          $personsToRemoveDetails,
          $resetProgress
        );
        break;

      case 'add_missing_only':
        $result['affected_persons'] = $this->previewAddMissingOnlyDetailed(
          $evaluation,
          $cycle,
          $personsToAddDetails
        );
        break;

      default:
        $result['errors'][] = "Modo de regeneración no válido: {$mode}";
        $result['will_execute'] = false;
        return $result;
    }

    return $result;
  }

  /**
   * Obtiene información RESUMIDA de personas con razón de cambio
   */
  private function getDetailedPersonsInfo($personIds, $evaluation, $cycle, $context = 'current')
  {
    if (empty($personIds)) {
      return [];
    }

    $persons = Worker::whereIn('id', $personIds)
      ->with(['position.hierarchicalCategory', 'position.area', 'sede'])
      ->get();

    return $persons->map(function ($person) use ($evaluation, $cycle, $context) {
      $hierarchicalCategory = $person->position?->hierarchicalCategory;

      // Determinar la razón según el contexto
      $reason = '';
      $hasProgress = false;
      $progressLost = null;

      if ($context === 'to_remove') {
        // Razones por las que se elimina
        $reasons = [];

        // Verificar si la persona ya no está en el ciclo
        $isInCycle = DB::table('rrhh_persona as p')
          ->join('rrhh_cargo as pos', 'pos.id', '=', 'p.cargo_id')
          ->join('gh_hierarchical_category_detail as hcd', 'hcd.position_id', '=', 'pos.id')
          ->join('gh_evaluation_cycle_category_detail as eccd', 'eccd.hierarchical_category_id', '=', 'hcd.hierarchical_category_id')
          ->where('eccd.cycle_id', $cycle->id)
          ->where('p.id', $person->id)
          ->exists();

        if (!$isInCycle) {
          $reasons[] = 'Ya no está en el ciclo actual';
        }
        if ($person->status_deleted != 1) {
          $reasons[] = 'Empleado inactivo';
        }
        if ($person->status_id != 22) {
          $reasons[] = 'Estado del empleado cambió';
        }
        if ($person->fecha_inicio > $cycle->cut_off_date) {
          $reasons[] = 'Fecha de inicio posterior a fecha de corte';
        }

        $reason = !empty($reasons) ? implode(', ', $reasons) : 'Eliminado por sincronización con ciclo';

        // Verificar si tiene progreso
        $personResult = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
          ->where('person_id', $person->id)
          ->first();

        if ($personResult) {
          $hasProgress = $personResult->result > 0 || $personResult->is_completed;
          $progressLost = [
            'result' => round($personResult->result, 2),
            'is_completed' => $personResult->is_completed,
            'completion_percentage' => round($personResult->completion_percentage * 100, 2),
          ];
        }
      } elseif ($context === 'to_add') {
        // Razones por las que se agrega
        $reason = 'Nueva persona en el ciclo';

        if ($person->fecha_inicio >= $cycle->start_date && $person->fecha_inicio <= $cycle->cut_off_date) {
          $reason = 'Ingresó durante el ciclo (fecha: ' . $person->fecha_inicio . ')';
        }
      } elseif ($context === 'current') {
        $personResult = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
          ->where('person_id', $person->id)
          ->first();

        if ($personResult && $personResult->is_completed) {
          $reason = 'Evaluación completada';
        } elseif ($personResult && $personResult->completion_percentage > 0) {
          $reason = 'Evaluación en progreso (' . round($personResult->completion_percentage * 100, 2) . '%)';
        } else {
          $reason = 'Sin iniciar';
        }
      }

      // Contar objetivos y competencias (solo totales)
      $objectivesCount = 0;
      $competencesCount = 0;

      if ($hierarchicalCategory && $context !== 'to_remove') {
        $objectivesCount = EvaluationCategoryObjectiveDetail::where('category_id', $hierarchicalCategory->id)
          ->where('person_id', $person->id)
          ->where('active', 1)
          ->whereHas('objective', fn($q) => $q->where('active', 1))
          ->whereNull('deleted_at')
          ->count();

        if (in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
          $competencesCount = DB::table('gh_evaluation_category_competence')
            ->join('gh_hierarchical_category_detail', 'gh_evaluation_category_competence.category_id', '=', 'gh_hierarchical_category_detail.hierarchical_category_id')
            ->where('gh_evaluation_category_competence.person_id', $person->id)
            ->where('gh_evaluation_category_competence.active', 1)
            ->where('gh_hierarchical_category_detail.position_id', $person->cargo_id)
            ->whereNull('gh_evaluation_category_competence.deleted_at')
            ->whereNull('gh_hierarchical_category_detail.deleted_at')
            ->count();
        }
      }

      $result = [
        'person_id' => $person->id,
        'name' => $person->nombre_completo,
        'dni' => $person->vat,
        'position' => $person->position?->name,
        'area' => $person->position?->area?->name,
        'sede' => $person->sede?->abreviatura,
        'hierarchical_category' => $hierarchicalCategory?->name,
        'reason' => $reason,
      ];

      // Solo agregar conteos si hay
      if ($objectivesCount > 0 || $competencesCount > 0) {
        $result['will_have'] = [];
        if ($objectivesCount > 0) {
          $result['will_have']['objectives'] = $objectivesCount;
        }
        if ($competencesCount > 0) {
          $result['will_have']['competences'] = $competencesCount;
        }
      }

      // Solo agregar progreso perdido si se elimina y tiene progreso
      if ($context === 'to_remove' && $hasProgress && $progressLost) {
        $result['progress_lost'] = $progressLost;
      }

      return $result;
    })->values()->toArray();
  }

  /**
   * Preview simplificado del modo full_reset
   */
  private function previewFullResetDetailed($evaluation, $cycle, $currentPersonsDetails, $expectedPersonIds)
  {
    $personsToCreate = $this->getDetailedPersonsInfo($expectedPersonIds, $evaluation, $cycle, 'to_add');

    // Contar personas con progreso que se perderá
    $personsWithProgress = array_filter($currentPersonsDetails, fn($p) => isset($p['progress_lost']));

    return [
      'mode' => 'full_reset',
      'description' => 'Reinicio completo - Elimina todo y crea desde cero',
      'impact' => 'ALTO - Se perderá todo el progreso existente',
      'summary' => [
        'total_will_delete' => count($currentPersonsDetails),
        'total_will_create' => count($personsToCreate),
        'persons_with_progress_lost' => count($personsWithProgress),
      ],
      'persons_to_delete' => $currentPersonsDetails,
      'persons_to_add' => $personsToCreate,
      'warnings' => [
        count($personsWithProgress) > 0
          ? 'Se perderá el progreso de ' . count($personsWithProgress) . ' persona(s)'
          : 'No hay progreso que se perderá',
      ],
    ];
  }

  /**
   * Preview simplificado del modo sync_with_cycle
   */
  private function previewSyncWithCycleDetailed($evaluation, $cycle, $currentPersonsDetails, $personsToAddDetails, $personsToRemoveDetails, $resetProgress)
  {
    $personsToKeep = array_filter($currentPersonsDetails, function ($person) use ($personsToRemoveDetails) {
      $removeIds = array_column($personsToRemoveDetails, 'person_id');
      return !in_array($person['person_id'], $removeIds);
    });

    // Contar personas que se eliminan con progreso
    $personsRemovedWithProgress = array_filter($personsToRemoveDetails, fn($p) => isset($p['progress_lost']));

    // Contar personas que se mantienen pero tienen progreso (si reset_progress=true, perderán su progreso)
    $personsToKeepWithProgress = array_filter($personsToKeep, function ($p) use ($evaluation) {
      // Verificar si tiene progreso actual
      $personResult = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
        ->where('person_id', $p['person_id'])
        ->first();

      return $personResult && ($personResult->result > 0 || $personResult->is_completed);
    });

    // Calcular total de personas con progreso que se perderá
    $totalProgressLost = count($personsRemovedWithProgress);
    if ($resetProgress) {
      $totalProgressLost += count($personsToKeepWithProgress);
    }

    return [
      'mode' => 'sync_with_cycle',
      'description' => 'Sincronizar con ciclo - Agregar nuevos y eliminar inactivos',
      'impact' => $resetProgress ? 'ALTO - Se resetearán resultados' : 'MEDIO - Solo afecta cambios',
      'summary' => [
        'total_will_remove' => count($personsToRemoveDetails),
        'total_will_add' => count($personsToAddDetails),
        'total_will_keep' => count($personsToKeep),
        'will_reset_progress' => $resetProgress,
        'persons_removed_with_progress' => count($personsRemovedWithProgress),
        'persons_kept_with_progress' => count($personsToKeepWithProgress),
        'total_persons_losing_progress' => $totalProgressLost,
      ],
      'persons_to_remove' => $personsToRemoveDetails,
      'persons_to_add' => $personsToAddDetails,
      'warnings' => array_filter([
        count($personsRemovedWithProgress) > 0
          ? 'Se perderá el progreso de ' . count($personsRemovedWithProgress) . ' persona(s) que se eliminan'
          : null,
        $resetProgress && count($personsToKeepWithProgress) > 0
          ? 'Se reseteará el progreso de ' . count($personsToKeepWithProgress) . ' persona(s) que se mantienen'
          : null,
        $resetProgress
          ? '⚠️ TOTAL: Se perderá el progreso de ' . $totalProgressLost . ' persona(s) en total'
          : null,
      ]),
    ];
  }

  /**
   * Preview simplificado del modo add_missing_only
   */
  private function previewAddMissingOnlyDetailed($evaluation, $cycle, $personsToAddDetails)
  {
    return [
      'mode' => 'add_missing_only',
      'description' => 'Agregar solo faltantes - No elimina nada',
      'impact' => 'BAJO - Solo agrega',
      'summary' => [
        'total_will_add' => count($personsToAddDetails),
        'no_deletions' => true,
        'no_modifications' => true,
      ],
      'persons_to_add' => $personsToAddDetails,
      'warnings' => count($personsToAddDetails) === 0
        ? ['No hay personas nuevas que agregar']
        : [],
    ];
  }

  public function regenerateEvaluation($evaluationId, array $params = [])
  {
    $evaluation = $this->find($evaluationId);

    // Parámetros por defecto
    $mode = $params['mode'] ?? 'sync_with_cycle';
    $resetProgress = $params['reset_progress'] ?? false;
    $force = $params['force'] ?? false;

    // Verificar si hay cambios en las personas del ciclo
    $cycleChanges = $this->checkCycleChanges($evaluation);
    $needsRegeneration = $force || $cycleChanges;

    if (!$needsRegeneration && $mode !== 'full_reset') {
      return [
        'success' => true,
        'message' => 'No se detectaron cambios en las personas del ciclo; no se realizaron modificaciones',
        'changes_detected' => false,
        'mode_used' => $mode
      ];
    }

    $result = match ($mode) {
      'full_reset' => $this->executeFullReset($evaluation, $resetProgress),
      'sync_with_cycle' => $this->executeSyncWithCycle($evaluation, $resetProgress),
      'add_missing_only' => $this->executeAddMissingOnly($evaluation),
      default => throw new Exception("Modo de regeneración no válido: {$mode}")
    };

    return array_merge($result, [
      'success' => true,
      'changes_detected' => true,
      'mode_used' => $mode
    ]);
  }

  /**
   * Modo: Reinicio completo - Elimina todo y crea desde cero
   */
  private function executeFullReset($evaluation, $resetProgress = false)
  {
    // Log::info("Iniciando regeneración completa para evaluación {$evaluation->id}");

    // 1. Limpiar completamente todos los datos existentes
    EvaluationPersonResult::where('evaluation_id', $evaluation->id)->delete();
    EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluation->id)->delete();

    // 2. Resetear dashboards
    EvaluationDashboard::where('evaluation_id', $evaluation->id)->get()->each->resetStats();
    EvaluationPersonDashboard::where('evaluation_id', $evaluation->id)->delete();

    // 3. Recrear todo desde cero
    $this->evaluationPersonResultService->storeMany($evaluation->id);
    $this->evaluationPersonService->storeMany($evaluation->id);

    // 4. Recrear competencias si es necesario
    $competencesCreated = 0;
    if (in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
      $competencesResult = $this->crearCompetenciasEvaluacion($evaluation);
      $competencesCreated = $competencesResult['competencias_creadas'] ?? 0;
    }

    return [
      'message' => 'Evaluación completamente regenerada desde cero',
      'participants_recreated' => EvaluationPersonResult::where('evaluation_id', $evaluation->id)->count(),
      'competences_created' => $competencesCreated,
      'progress_reset' => true
    ];
  }

  /**
   * Modo: Sincronizar con ciclo - Agregar nuevos, mantener existentes según configuración
   */
  private function executeSyncWithCycle($evaluation, $resetProgress = false)
  {
    // Log::info("Iniciando sincronización con ciclo para evaluación {$evaluation->id}");

    // Personas actualmente en la evaluación
    $currentPersons = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->pluck('person_id')
      ->toArray();

    // Personas que deberían estar según el ciclo actual
    $expectedPersons = $this->getPersonsFromCycle($evaluation->cycle_id);

    // Calcular diferencias
    $personsToAdd = array_diff($expectedPersons, $currentPersons);
    $personsToRemove = array_diff($currentPersons, $expectedPersons);

    // Diagnóstico detallado para debugging
    $diagnostico = [
      'current_persons_count' => count($currentPersons),
      'expected_persons_count' => count($expectedPersons),
      'persons_to_add_ids' => array_values($personsToAdd),
      'persons_to_remove_ids' => array_values($personsToRemove),
      'current_persons_sample' => array_slice($currentPersons, 0, 5),
      'expected_persons_sample' => array_slice($expectedPersons, 0, 5)
    ];

    $stats = [
      'persons_added' => 0,
      'persons_removed' => 0,
      'competences_created' => 0,
      'progress_reset_count' => 0
    ];

    // Eliminar personas que ya no están en el ciclo
    if (!empty($personsToRemove)) {
      EvaluationPersonResult::where('evaluation_id', $evaluation->id)
        ->whereIn('person_id', $personsToRemove)
        ->delete();

      EvaluationPerson::where('evaluation_id', $evaluation->id)
        ->whereIn('person_id', $personsToRemove)
        ->delete();

      EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluation->id)
        ->whereIn('person_id', $personsToRemove)
        ->delete();

      $stats['persons_removed'] = count($personsToRemove);
    }

    // Agregar nuevas personas
    $personsAddedDetails = [];
    if (!empty($personsToAdd)) {
      $personsAddedDetails = $this->createPersonResultsForSpecific($evaluation, $personsToAdd);
      $this->createPersonDetailsForSpecific($evaluation, $personsToAdd);
      $stats['persons_added'] = count($personsAddedDetails);

      // Crear competencias solo para las personas nuevas
      if (in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        $stats['competences_created'] = $this->createCompetencesForSpecificPersons($evaluation, $personsToAdd);
      }
    }

    // Resetear progreso si se solicita
    if ($resetProgress) {
      EvaluationPersonResult::where('evaluation_id', $evaluation->id)
        ->update([
          'result' => 0,
          'objectivesResult' => 0,
          'competencesResult' => 0,
          'status' => 0
        ]);

      EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluation->id)
        ->update(['result' => 0]);

      $stats['progress_reset_count'] = EvaluationPersonResult::where('evaluation_id', $evaluation->id)->count();
    }

    // Resetear dashboards
    EvaluationDashboard::where('evaluation_id', $evaluation->id)->get()->each->resetStats();
    EvaluationPersonDashboard::where('evaluation_id', $evaluation->id)->delete();

    return [
      'message' => 'Evaluación sincronizada con el ciclo',
      'persons_added' => $stats['persons_added'],
      'persons_removed' => $stats['persons_removed'],
      'competences_created' => $stats['competences_created'],
      'progress_reset' => $resetProgress,
      'progress_reset_count' => $stats['progress_reset_count'],
      'persons_added_details' => $personsAddedDetails,
      'diagnostic' => $diagnostico
    ];
  }

  /**
   * Modo: Solo agregar faltantes - Mantener todo existente, solo agregar nuevos
   */
  private function executeAddMissingOnly($evaluation)
  {
    // Log::info("Agregando solo participantes faltantes para evaluación {$evaluation->id}");

    // Personas actualmente en la evaluación
    $currentPersons = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->pluck('person_id')
      ->toArray();

    // Personas que deberían estar según el ciclo actual
    $expectedPersons = $this->getPersonsFromCycle($evaluation->cycle_id);

    // Solo agregar los que faltan
    $personsToAdd = array_diff($expectedPersons, $currentPersons);

    // Diagnóstico detallado para debugging
    $diagnostico = [
      'current_persons_count' => count($currentPersons),
      'expected_persons_count' => count($expectedPersons),
      'persons_to_add_ids' => array_values($personsToAdd),
      'current_persons_sample' => array_slice($currentPersons, 0, 5),
      'expected_persons_sample' => array_slice($expectedPersons, 0, 5)
    ];

    $stats = [
      'persons_added' => 0,
      'competences_created' => 0
    ];

    $personsAddedDetails = [];
    if (!empty($personsToAdd)) {
      $personsAddedDetails = $this->createPersonResultsForSpecific($evaluation, $personsToAdd);
      $this->createPersonDetailsForSpecific($evaluation, $personsToAdd);
      $stats['persons_added'] = count($personsAddedDetails);

      // Crear competencias solo para las personas nuevas
      if (in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        $stats['competences_created'] = $this->createCompetencesForSpecificPersons($evaluation, $personsToAdd);
      }
    }

    // Resetear dashboards para que reflejen las nuevas personas
    EvaluationDashboard::where('evaluation_id', $evaluation->id)->get()->each->resetStats();
    EvaluationPersonDashboard::where('evaluation_id', $evaluation->id)->delete();

    // Disparar job para regenerar dashboards automáticamente
    UpdateEvaluationDashboards::dispatch($evaluation->id, true)
      ->onQueue('evaluation-dashboards');

    return [
      'message' => empty($personsAddedDetails) ? 'No hay participantes faltantes que agregar' : 'Participantes faltantes agregados exitosamente',
      'persons_added' => $stats['persons_added'],
      'competences_created' => $stats['competences_created'],
      'existing_preserved' => true,
      'persons_added_details' => $personsAddedDetails,
      'diagnostic' => $diagnostico
    ];
  }

  /**
   * Crear competencias solo para personas específicas
   */
  private function createCompetencesForSpecificPersons($evaluation, array $personIds)
  {
    $competencesCreated = 0;

    foreach ($personIds as $personId) {
      $persona = Worker::find($personId);
      if (!$persona) continue;

      if ($evaluation->typeEvaluation == self::EVALUACION_180) {
        $competencesCreated += $this->procesarEvaluacion180($evaluation, $persona);
      } else {
        $competencesCreated += $this->procesarEvaluacion360($evaluation, $persona);
      }
    }

    return $competencesCreated;
  }

  /**
   * Crear EvaluationPersonResult solo para personas específicas
   */
  private function createPersonResultsForSpecific($evaluation, array $personIds)
  {
    $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);
    $personsAdded = [];

    foreach ($personIds as $personId) {
      // Verificar si ya existe
      $exists = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
        ->where('person_id', $personId)
        ->exists();

      if ($exists) continue;

      $person = Worker::find($personId);
      if (!$person || !$person->position || !$person->position->hierarchicalCategory) continue;

      // Verificar fecha de inicio vs fecha de corte
      if ($person->fecha_inicio > $cycle->cut_off_date) continue;

      $hierarchicalCategory = $person->position->hierarchicalCategory;
      $objectivesPercentage = $hierarchicalCategory->hasObjectives ? $evaluation->objectivesPercentage : 0;
      $competencesPercentage = $evaluation->typeEvaluation == 0 ? 0 : $evaluation->competencesPercentage;

      $evaluator = $person->evaluator ?? throw new Exception('La persona ' . $person->nombre_completo . ' de la categoría ' . $person->position->hierarchicalCategory->name . ' no tiene un evaluador asignado.');

      EvaluationPersonResult::create([
        'person_id' => $person->id,
        'evaluation_id' => $evaluation->id,
        'competencesPercentage' => $competencesPercentage,
        'objectivesPercentage' => $objectivesPercentage,
        'objectivesResult' => 0,
        'competencesResult' => 0,
        'status' => 0,
        'result' => 0,
        'name' => $person->nombre_completo,
        'dni' => $person->vat,
        'hierarchical_category' => $hierarchicalCategory->name,
        'position' => $person->position->name,
        'area' => $person->position->area->name ?? '',
        'sede' => $person->sede->abreviatura ?? '',
        'boss' => $evaluator->nombre_completo ?? '',
        'boss_dni' => $evaluator->vat ?? '',
        'boss_hierarchical_category' => $evaluator->position->hierarchicalCategory->name ?? '',
        'boss_position' => $evaluator->position->name ?? '',
        'boss_area' => $evaluator->position->area->name ?? '',
        'boss_sede' => $evaluator->sede->abreviatura ?? '',
        'hasObjectives' => $hierarchicalCategory->hasObjectives,
        'hierarchical_category_id' => $hierarchicalCategory->id,
      ]);

      // Agregar detalles de la persona agregada para el log
      $personsAdded[] = [
        'id' => $person->id,
        'name' => $person->nombre_completo,
        'dni' => $person->vat,
        'position' => $person->position->name,
        'area' => $person->position->area->name ?? '',
        'hierarchical_category' => $hierarchicalCategory->name,
        'fecha_inicio' => $person->fecha_inicio,
        'reason' => 'Agregado por diferencia en ciclo'
      ];
    }

    return $personsAdded;
  }

  /**
   * Crear EvaluationPerson (objetivos) solo para personas específicas
   */
  private function createPersonDetailsForSpecific($evaluation, array $personIds)
  {
    $cycle = EvaluationCycle::findOrFail($evaluation->cycle_id);

    // Obtener detalles del ciclo solo para las personas específicas
    $cycleDetails = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
      ->whereIn('person_id', $personIds)
      ->get();

    foreach ($cycleDetails as $detail) {
      // Verificar si ya existe este person_cycle_detail para esta evaluación
      $exists = EvaluationPerson::where('evaluation_id', $evaluation->id)
        ->where('person_cycle_detail_id', $detail->id)
        ->exists();

      if (!$exists) {
        EvaluationPerson::create([
          'person_id' => $detail->person_id,
          'chief_id' => $detail->chief_id,
          'chief' => $detail->chief,
          'person_cycle_detail_id' => $detail->id,
          'evaluation_id' => $evaluation->id,
          'result' => 0,
          'compliance' => 0,
          'qualification' => 0,
          'wasEvaluated' => 0,
        ]);
      }
    }
  }

  public function update($data)
  {
    DB::beginTransaction();

    try {
      $evaluation = $this->find($data['id']);

      $data = $this->enrichData($data, $evaluation);
      $evaluation->update($data);

      DB::commit();

      return new EvaluationResource($evaluation);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Verificar si hay cambios en las personas del ciclo
   */
  private function checkCycleChanges($evaluation)
  {
    // Personas actualmente en la evaluación
    $currentPersons = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->pluck('person_id')
      ->toArray();

    // Personas que deberían estar según el ciclo actual
    $expectedPersons = $this->getPersonsFromCycle($evaluation->cycle_id);

    // Verificar si hay diferencias
    $personsToAdd = array_diff($expectedPersons, $currentPersons);
    $personsToRemove = array_diff($currentPersons, $expectedPersons);

    return !empty($personsToAdd) || !empty($personsToRemove);
  }

  /**
   * Obtener personas del ciclo según categorías jerárquicas
   * IMPORTANTE: Aplica las mismas validaciones que createPersonResultsForSpecific
   */
  private function getPersonsFromCycle($cycleId)
  {
    $cycle = EvaluationCycle::findOrFail($cycleId);

    $query = DB::table('rrhh_persona as p')
      ->join('rrhh_cargo as pos', 'pos.id', '=', 'p.cargo_id')
      ->join('gh_hierarchical_category_detail as hcd', 'hcd.position_id', '=', 'pos.id')
      ->join('gh_hierarchical_category as hc', 'hc.id', '=', 'hcd.hierarchical_category_id')
      ->join('gh_evaluation_cycle_category_detail as eccd', 'eccd.hierarchical_category_id', '=', 'hc.id')
      ->where('eccd.cycle_id', $cycleId)
      ->whereNull('eccd.deleted_at')
      ->where('p.status_deleted', 1)
      ->where('p.b_empleado', 1)
      ->where('p.status_id', 22)
      // CRÍTICO: Validar fecha de inicio <= fecha de corte (igual que en createPersonResultsForSpecific)
      ->where('p.fecha_inicio', '<=', $cycle->cut_off_date)
      // CRÍTICO: Debe tener evaluador asignado (igual que en createPersonResultsForSpecific)
      ->where(function ($q) {
        $q->whereNotNull('p.supervisor_id')
          ->orWhereNotNull('p.jefe_id');
      });

    if ($cycle->typeEvaluation == 0) {
      // Solo categorías con objetivos
      $query->where('hc.hasObjectives', true);
    }

    return $query->distinct()
      ->pluck('p.id')
      ->toArray();
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);
    DB::transaction(function () use ($evaluationCompetence) {
      $evaluationCompetence->delete();
    });
    return response()->json(['message' => 'Evaluación eliminada correctamente']);
  }

  /**
   * Método público para crear competencias manualmente si es necesario
   */
  public function createCompetences($evaluationId)
  {
    $evaluation = $this->find($evaluationId);

    if (!in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
      return [
        'success' => false,
        'message' => 'La evaluación debe ser de tipo 180° o 360°'
      ];
    }

    return $this->crearCompetenciasEvaluacion($evaluation);
  }
}
