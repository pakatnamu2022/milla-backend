<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationModel;
use App\Models\gp\gestionhumana\evaluacion\EvaluationParameter;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\personal\Worker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonCompetenceDetailService extends BaseService
{
  // Tipos de evaluador según tu config
  const TIPO_EVALUADOR_JEFE = 0;           // Líder directo
  const TIPO_EVALUADOR_AUTOEVALUACION = 1; // Autoevaluación
  const TIPO_EVALUADOR_COMPANEROS = 2;     // Compañeros
  const TIPO_EVALUADOR_REPORTES = 3;       // Reportes/Subordinados

  // Tipos de evaluación según tu config
  const EVALUACION_OBJETIVOS = 0;
  const EVALUACION_180 = 1;
  const EVALUACION_360 = 2;

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationPersonCompetenceDetail::class,
      $request,
      EvaluationPersonCompetenceDetail::filters ?? [],
      EvaluationPersonCompetenceDetail::sorts ?? [],
      EvaluationPersonCompetenceDetailResource::class,
    );
  }

  public function listByEvaluation(int $evaluationId, Request $request)
  {
    if (filter_var($request->query('grouped', false), FILTER_VALIDATE_BOOLEAN)) {
      return $this->listByEvaluationGrouped($evaluationId, $request);
    }

    $query = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId);
    return $this->getFilteredResults(
      $query,
      $request,
      EvaluationPersonCompetenceDetail::filters ?? [],
      EvaluationPersonCompetenceDetail::sorts ?? [],
      EvaluationPersonCompetenceDetailResource::class,
    );
  }

  private function listByEvaluationGrouped(int $evaluationId, Request $request): \Illuminate\Http\JsonResponse
  {
    $perPage  = min((int) $request->query('per_page', 15), 100);
    $page     = max((int) $request->query('page', 1), 1);
    $sortDir  = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

    $baseQuery = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId);
    $baseQuery = $this->applyFilters($baseQuery, $request, EvaluationPersonCompetenceDetail::filters ?? []);

    // Distinct (person_id, competence_id) pairs — these are the pagination units
    $pairs = (clone $baseQuery)
      ->selectRaw('person_id, competence_id, MIN(person) as person, MIN(competence) as competence')
      ->groupBy('person_id', 'competence_id')
      ->orderBy('person', $sortDir)
      ->orderBy('competence', $sortDir)
      ->get();

    $total    = $pairs->count();
    $lastPage = max(1, (int) ceil($total / $perPage));
    $page     = min($page, $lastPage);
    $from     = $total > 0 ? (($page - 1) * $perPage) + 1 : null;
    $to       = $total > 0 ? min($from + $perPage - 1, $total) : null;

    $pagePairs = $pairs->slice(($page - 1) * $perPage, $perPage)->values();

    // Load subcompetences only for this page's pairs
    $grouped = collect();
    if ($pagePairs->isNotEmpty()) {
      $grouped = (clone $baseQuery)
        ->where(function ($q) use ($pagePairs) {
          foreach ($pagePairs as $pair) {
            $q->orWhere(fn($sq) => $sq
              ->where('person_id', $pair->person_id)
              ->where('competence_id', $pair->competence_id));
          }
        })
        ->get()
        ->groupBy(fn($r) => $r->person_id . '_' . $r->competence_id);
    }

    $data = $pagePairs->map(function ($pair) use ($grouped) {
      $key = $pair->person_id . '_' . $pair->competence_id;
      return [
        'person_id'      => $pair->person_id,
        'person'         => $pair->person,
        'competence_id'  => $pair->competence_id,
        'competence'     => $pair->competence,
        'subcompetences' => $grouped->get($key, collect())->map(fn($r) => [
          'id'               => $r->id,
          'sub_competence_id' => $r->sub_competence_id,
          'sub_competence'   => $r->sub_competence,
          'evaluator_id'     => $r->evaluator_id,
          'evaluator'        => $r->evaluator,
          'evaluatorType'    => $r->evaluatorType,
          'result'           => $r->result,
        ])->values(),
      ];
    });

    $baseUrl     = $request->url();
    $queryParams = $request->query();
    $first       = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
    $last        = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $lastPage]));
    $prev        = $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null;
    $next        = $page < $lastPage ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null;

    return response()->json([
      'data'  => $data,
      'links' => compact('first', 'last', 'prev', 'next'),
      'meta'  => [
        'current_page' => $page,
        'from'         => $from,
        'last_page'    => $lastPage,
        'links'        => $this->generatePaginationLinks($page, $lastPage, $baseUrl, $queryParams),
        'path'         => $baseUrl,
        'per_page'     => $perPage,
        'to'           => $to,
        'total'        => $total,
      ],
    ]);
  }

  public function find($id)
  {
    $competenceDetail = EvaluationPersonCompetenceDetail::find($id);
    if (!$competenceDetail) {
      throw new Exception('Detalle de competencia no encontrado');
    }
    return $competenceDetail;
  }

  public function store($data)
  {
    DB::beginTransaction();
    try {
      $competenceDetail = EvaluationPersonCompetenceDetail::create($data);

      // Recalcular resultados después de crear
      $this->recalculatePersonResults($competenceDetail->evaluation_id, $competenceDetail->person_id);

      DB::commit();
      return new EvaluationPersonCompetenceDetailResource($competenceDetail);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new EvaluationPersonCompetenceDetailResource($this->find($id));
  }

  public function update($data)
  {
    DB::beginTransaction();
    try {
      $competenceDetail = $this->find($data['id']);
      $competenceDetail->update($data);

      // Recalcular resultados después de actualizar
      $this->recalculatePersonResults($competenceDetail->evaluation_id, $competenceDetail->person_id);

      DB::commit();
      return new EvaluationPersonCompetenceDetailResource($competenceDetail);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $competenceDetail = $this->find($id);
      $evaluationId = $competenceDetail->evaluation_id;
      $personId = $competenceDetail->person_id;

      $competenceDetail->delete();

      // Recalcular resultados después de eliminar
      $this->recalculatePersonResults($evaluationId, $personId);

      DB::commit();
      return ['message' => 'Detalle de competencia eliminado correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroyMany(array $ids, bool $cascade = false)
  {
    DB::beginTransaction();
    try {
      $records = EvaluationPersonCompetenceDetail::whereIn('id', $ids)->get();

      $pairMap = $records->unique(fn($r) => $r->evaluation_id . '_' . $r->person_id)
        ->mapWithKeys(fn($r) => [$r->evaluation_id . '_' . $r->person_id => [
          'evaluation_id' => $r->evaluation_id,
          'person_id'     => $r->person_id,
        ]]);

      // Capturar pares (person_id, competence_id) antes de eliminar, si hay cascade
      $cascadePairs = $cascade
        ? $records->unique(fn($r) => $r->person_id . '_' . $r->competence_id)
            ->map(fn($r) => ['person_id' => $r->person_id, 'competence_id' => $r->competence_id])
        : collect();

      EvaluationPersonCompetenceDetail::whereIn('id', $ids)->delete();

      foreach ($pairMap as $pair) {
        $this->recalculatePersonResults($pair['evaluation_id'], $pair['person_id']);
      }

      $deletedCategory = 0;
      foreach ($cascadePairs as $pair) {
        $deletedCategory += EvaluationCategoryCompetenceDetail::where('person_id', $pair['person_id'])
          ->where('competence_id', $pair['competence_id'])
          ->delete();
      }

      DB::commit();

      $result = ['message' => 'Detalles de competencia eliminados correctamente', 'deleted' => count($ids)];
      if ($cascade) {
        $result['deleted_category_assignments'] = $deletedCategory;
      }
      return $result;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Obtener competencias por evaluación y persona
   */
  public function getByEvaluationAndPerson($evaluationId, $personId)
  {
    $competences = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->get();

    return EvaluationPersonCompetenceDetailResource::collection($competences);
  }

  /**
   * Actualizar múltiples competencias de una persona
   */
  public function updateMany($data)
  {
    DB::beginTransaction();
    try {
      $evaluationId = $data['evaluation_id'];
      $personId = $data['person_id'];
      $competences = $data['competences']; // Array de competencias a actualizar

      foreach ($competences as $competenceData) {
        $competenceDetail = $this->find($competenceData['id']);
        $competenceDetail->update([
          'result' => $competenceData['result'] ?? $competenceDetail->result
        ]);
      }

      // Recalcular resultados después de actualizar todas las competencias
      $this->recalculatePersonResults($evaluationId, $personId);

      DB::commit();
      return $this->getByEvaluationAndPerson($evaluationId, $personId);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Recalcular los resultados de una persona en una evaluación
   */
  private function recalculatePersonResults($evaluationId, $personId)
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    // Calcular resultado de competencias
    $competencesResult = $this->calculateCompetencesResult($evaluationId, $personId, $evaluation->typeEvaluation);

    // Calcular resultado de objetivos (si aplica)
    $objectivesResult = $this->calculateObjectivesResult($evaluationId, $personId);

    // Actualizar EvaluationPersonResult
    $this->updatePersonResult($evaluationId, $personId, $competencesResult, $objectivesResult);
  }


  /**
   * Calcular resultado de competencias según el tipo de evaluación
   */
  public function calculateCompetencesResult($evaluationId, $personId, $evaluationType)
  {
    $evaluation = Evaluation::find($evaluationId);
    $maxScore = $evaluation->typeEvaluation !== Evaluation::EVALUATION_TYPE_OBJECTIVES ? $evaluation?->max_score_competence : 0; // Como atributo

    $competences = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)->get();

    if ($competences->isEmpty()) {
      return 0;
    }

    switch ($evaluationType) {
      case Evaluation::EVALUATION_TYPE_180:
        // Para 180°, solo considerar evaluación del jefe
        return ($this->calculate180CompetencesResult($competences) / $maxScore) * 100;

      case Evaluation::EVALUATION_TYPE_360:
        // Para 360°, calcular promedio ponderado por tipo de evaluador
        return ($this->calculate360CompetencesResult($competences, $personId) / $maxScore) * 100;

      default:
        // Para evaluación de objetivos, promedio simple
        return $competences->avg('result') ?? 0;
    }
  }

  /**
   * Calcular resultado de competencias para evaluación 180°
   */
  private function calculate180CompetencesResult($competences)
  {
    // Pesos por tipo de evaluador (esto puede ser configurable)
    $weights = [
      self::TIPO_EVALUADOR_JEFE => 0.9,          // 40% jefe
      self::TIPO_EVALUADOR_AUTOEVALUACION => 0.1, // 20% autoevaluación
    ];

    $totalWeightedScore = 0;
    $totalWeight = 0;

    foreach ($weights as $evaluatorType => $weight) {
      $typeCompetences = $competences->where('evaluatorType', $evaluatorType);

      if ($typeCompetences->isNotEmpty()) {
        $avgScore = $typeCompetences->avg('result');
        $totalWeightedScore += $avgScore * $weight;
        $totalWeight += $weight;
      }
    }

    return $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
  }

  /**
   * Calcular resultado de competencias para evaluación 360°
   */
  private function calculate360CompetencesResult($competences, $personId)
  {
    // Obtener la persona y su categoría jerárquica
    $person = Worker::with('position.hierarchicalCategory')->find($personId);

    if (!$person || !$person->position || !$person->position->hierarchicalCategory) {
      // Si no hay categoría, usar pesos por defecto
      $weights = [
        self::TIPO_EVALUADOR_JEFE => 0.4,
        self::TIPO_EVALUADOR_AUTOEVALUACION => 0.2,
        self::TIPO_EVALUADOR_COMPANEROS => 0.25,
        self::TIPO_EVALUADOR_REPORTES => 0.15
      ];
    } else {
      // Obtener el modelo de evaluación para esta categoría
      $categoryId = $person->position->hierarchicalCategory->id;
      $evaluationModel = EvaluationModel::getModelByCategory($categoryId);

      if ($evaluationModel) {
        // Usar los pesos del modelo (convertir de porcentaje a decimal)
        $weights = [
          self::TIPO_EVALUADOR_JEFE => $evaluationModel->leadership_weight / 100,
          self::TIPO_EVALUADOR_AUTOEVALUACION => $evaluationModel->self_weight / 100,
          self::TIPO_EVALUADOR_COMPANEROS => $evaluationModel->par_weight / 100,
          self::TIPO_EVALUADOR_REPORTES => $evaluationModel->report_weight / 100
        ];
      } else {
        // Si no hay modelo, usar pesos por defecto
        $weights = [
          self::TIPO_EVALUADOR_JEFE => 0.4,
          self::TIPO_EVALUADOR_AUTOEVALUACION => 0.2,
          self::TIPO_EVALUADOR_COMPANEROS => 0.25,
          self::TIPO_EVALUADOR_REPORTES => 0.15
        ];
      }
    }

    $totalWeightedScore = 0;
    $totalWeight = 0;

    foreach ($weights as $evaluatorType => $weight) {
      // Solo considerar tipos de evaluador con peso mayor a 0
      if ($weight > 0) {
        $typeCompetences = $competences->where('evaluatorType', $evaluatorType);

        if ($typeCompetences->isNotEmpty()) {
          $avgScore = $typeCompetences->avg('result');
          $totalWeightedScore += $avgScore * $weight;
          $totalWeight += $weight;
        }
      }
    }

    return $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
  }

  /**
   * Calcular resultado de objetivos
   */
  public function calculateObjectivesResult($evaluationId, $personId)
  {
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->with('personCycleDetail')
      ->get();

    if ($evaluationPersons->isEmpty()) {
      return 0;
    }

    // Calcular promedio ponderado por peso de cada objetivo
    $totalWeightedScore = 0;

    foreach ($evaluationPersons as $evaluationPerson) {
      $weight = $evaluationPerson->personCycleDetail->weight ?? 0;
      $qualification = $evaluationPerson->qualification ?? 0;

      $totalWeightedScore += $qualification * ($weight / 100); // Convertir peso a decimal
    }

    return $totalWeightedScore;
  }

  /**
   * Actualizar EvaluationPersonResult
   */
  public function updatePersonResult($evaluationId, $personId, $competencesResult, $objectivesResult)
  {
    $personResult = EvaluationPersonResult::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->first();

    if ($personResult) {
      $evaluation = Evaluation::find($evaluationId);
      $maxFinalScore = $evaluation->max_score_final;
      $maxCompetenceScore = 100;
      $maxObjectiveScore = $evaluation->max_score_objective;

      if (!$maxFinalScore) {
        throw new Exception('El parámetro final de evaluación no está configurado.');
      }
      if (!$maxCompetenceScore && $competencesResult > 0) {
        throw new Exception('El parámetro de competencia de evaluación no está configurado.');
      }
      if (!$maxObjectiveScore && $objectivesResult > 0) {
        throw new Exception('El parámetro de objetivo de evaluación no está configurado.');
      }

      // Convertir porcentajes a decimales
      $competencesPercentage = $personResult->competencesPercentage / 100;
      $objectivesPercentage = $personResult->objectivesPercentage / 100;

      // Normalizar los resultados a la escala del maxFinalScore
      $normalizedCompetencesResult = ($competencesResult / 100) * $maxFinalScore;
      $normalizedObjectivesResult = ($objectivesResult / $maxObjectiveScore) * $maxFinalScore;

      $finalResult = ($normalizedCompetencesResult * $competencesPercentage) + ($normalizedObjectivesResult * $objectivesPercentage);

      $personResult->update([
        'competencesResult' => $competencesResult,
        'objectivesResult' => $objectivesResult,
        'result' => $finalResult
      ]);
    }
  }

  // Métodos existentes del servicio original...

  public function crearEvaluacionCompetencias($evaluacionId)
  {
    try {
      DB::beginTransaction();

      $evaluacion = Evaluation::findOrFail($evaluacionId);

      // Validar que la evaluación sea 180° o 360°
      if (!in_array($evaluacion->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        throw new Exception('La evaluación debe ser de tipo 180° o 360° para crear competencias');
      }

      // Obtener todas las personas que participarán en esta evaluación
      $personasResultado = EvaluationPersonResult::where('evaluation_id', $evaluacionId)->get();

      if ($personasResultado->isEmpty()) {
        throw new Exception('No se encontraron personas para esta evaluación. Ejecute primero storeMany en EvaluationPersonResult');
      }

      // Limpiar competencias existentes para esta evaluación
      EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluacionId)->delete();

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

      DB::commit();

      return [
        'success' => true,
        'message' => 'Evaluación de competencias creada exitosamente',
        'data' => [
          'tipo_evaluacion' => $evaluacion->tipo_evaluacion_texto ?? config('evaluation.typesEvaluation')[$evaluacion->typeEvaluation],
          'personas_procesadas' => $totalPersonasProcesadas,
          'competencias_creadas' => $totalCompetenciasCreadas,
          'configuracion' => [
            'autoevaluacion' => $evaluacion->selfEvaluation,
            'evaluacion_companeros' => $evaluacion->partnersEvaluation
          ]
        ]
      ];
    } catch (Exception $e) {
      DB::rollBack();

      return [
        'success' => false,
        'message' => 'Error al crear la evaluación de competencias: ' . $e->getMessage()
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

    // Determinar la estructura jerárquica de la persona
    $tieneJefe = !is_null($persona->jefe_id);
    $tieneSubordinados = $this->tieneSubordinados($persona->id);

    // Obtener competencias según tu estructura de categorías jerárquicas
    $competenciasData = $this->obtenerCompetenciasParaPersona($persona);

    foreach ($competenciasData as $competenciaData) {
      // 1. Autoevaluación (si está habilitada)
      if ($evaluacion->selfEvaluation) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->id,
          self::TIPO_EVALUADOR_AUTOEVALUACION
        );
        $competenciasCreadas++;
      }

      // 2. Evaluación del jefe directo
      if ($tieneJefe) {
        $this->crearDetalleCompetencia(
          $evaluacion->id,
          $persona,
          $competenciaData,
          $persona->jefe_id,
          self::TIPO_EVALUADOR_JEFE
        );
        $competenciasCreadas++;
      }

      // 3. Evaluación de compañeros (si está habilitada)
      if ($evaluacion->partnersEvaluation && $tieneJefe) {
        $companeros = $this->obtenerCompaneros($persona);
        foreach ($companeros as $companero) {
          $this->crearDetalleCompetencia(
            $evaluacion->id,
            $persona,
            $competenciaData,
            $companero->id,
            self::TIPO_EVALUADOR_COMPANEROS
          );
          $competenciasCreadas++;
        }
      }

      // 4. Evaluación de reportes directos (solo si tiene subordinados)
      if ($tieneSubordinados) {
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
      'person_id' => $persona->id,
      'evaluator_id' => $evaluadorId,
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
      ->where(function ($q) use ($persona) {
        $q->whereNull('gh_evaluation_category_competence.person_id')
          ->orWhere('gh_evaluation_category_competence.person_id', $persona->id);
      })
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
      ->distinct()
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

  /**
   * Preview de sincronización: muestra qué se eliminaría y qué se agregaría
   * sin aplicar ningún cambio.
   */
  public function previewSyncCompetences(int $evaluationId, ?int $personId = null): array
  {
    $evaluation = Evaluation::findOrFail($evaluationId);

    if (!in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
      throw new Exception('Solo se puede sincronizar evaluaciones de tipo 180° o 360°.');
    }

    $personResultsQuery = EvaluationPersonResult::where('evaluation_id', $evaluationId);
    if ($personId) {
      $personResultsQuery->where('person_id', $personId);
    }
    $personResults = $personResultsQuery->get();

    if ($personResults->isEmpty()) {
      throw new Exception('No se encontraron personas para esta evaluación.');
    }

    $preview = [
      'evaluation_id' => $evaluationId,
      'evaluation_name' => $evaluation->name ?? null,
      'total_a_eliminar' => 0,
      'total_sin_respuesta_a_eliminar' => 0,
      'total_con_respuesta_a_eliminar' => 0,
      'total_a_agregar' => 0,
      'personas' => [],
    ];

    foreach ($personResults as $personResult) {
      $persona = Worker::find($personResult->person_id);
      if (!$persona) continue;

      $templateCompetencies = $this->obtenerCompetenciasParaPersona($persona);
      $templateKeys = collect($templateCompetencies)
        ->mapWithKeys(fn($c) => [$c['competence_id'] . '_' . $c['sub_competence_id'] => true]);

      $currentRecords = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
        ->where('person_id', $persona->id)
        ->get();

      $toDelete = [];
      foreach ($currentRecords as $record) {
        $key = $record->competence_id . '_' . $record->sub_competence_id;
        if (!$templateKeys->has($key)) {
          $toDelete[] = [
            'id' => $record->id,
            'competence' => $record->competence,
            'sub_competence' => $record->sub_competence,
            'evaluatorType' => $record->evaluatorType,
            'result' => $record->result,
            'tiene_respuesta' => $record->result != 0,
          ];
        }
      }

      $toAdd = $this->calcularRegistrosAgregar($evaluation, $persona, $templateCompetencies);

      $sinRespuesta = collect($toDelete)->where('tiene_respuesta', false)->count();
      $conRespuesta = collect($toDelete)->where('tiene_respuesta', true)->count();

      $preview['total_a_eliminar'] += count($toDelete);
      $preview['total_sin_respuesta_a_eliminar'] += $sinRespuesta;
      $preview['total_con_respuesta_a_eliminar'] += $conRespuesta;
      $preview['total_a_agregar'] += count($toAdd);

      $preview['personas'][] = [
        'person_id' => $persona->id,
        'name' => $persona->nombre_completo,
        'a_eliminar' => $toDelete,
        'a_agregar' => $toAdd,
      ];
    }

    return $preview;
  }

  /**
   * Sincroniza las competencias de una evaluación con la plantilla de CategoryCompetence.
   * Regla: si la competencia está en CategoryCompetence se mantiene/agrega,
   * si no está se elimina (prioridad: primero sin respuesta, luego con respuesta).
   * Luego recalcula resultados por persona.
   */
  public function syncCompetencesForEvaluation(int $evaluationId, ?int $personId = null): array
  {
    return DB::transaction(function () use ($evaluationId, $personId) {
      $evaluation = Evaluation::findOrFail($evaluationId);

      if (!in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        throw new Exception('Solo se puede sincronizar evaluaciones de tipo 180° o 360°.');
      }

      $personResultsQuery = EvaluationPersonResult::where('evaluation_id', $evaluationId);
      if ($personId) {
        $personResultsQuery->where('person_id', $personId);
      }
      $personResults = $personResultsQuery->get();

      if ($personResults->isEmpty()) {
        throw new Exception('No se encontraron personas para esta evaluación.');
      }

      $stats = [
        'evaluation_id' => $evaluationId,
        'personas_procesadas' => 0,
        'registros_eliminados' => 0,
        'registros_sin_respuesta_eliminados' => 0,
        'registros_con_respuesta_eliminados' => 0,
        'registros_agregados' => 0,
        'personas' => [],
      ];

      foreach ($personResults as $personResult) {
        $persona = Worker::find($personResult->person_id);
        if (!$persona) continue;

        $templateCompetencies = $this->obtenerCompetenciasParaPersona($persona);
        $templateKeys = collect($templateCompetencies)
          ->mapWithKeys(fn($c) => [$c['competence_id'] . '_' . $c['sub_competence_id'] => true]);

        $currentRecords = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
          ->where('person_id', $persona->id)
          ->get();

        $deletedNoResponse = 0;
        $deletedWithResponse = 0;

        // Prioridad 1: eliminar sin respuesta (result = 0) que no están en template
        foreach ($currentRecords->where('result', 0) as $record) {
          if (!$templateKeys->has($record->competence_id . '_' . $record->sub_competence_id)) {
            $record->delete();
            $deletedNoResponse++;
          }
        }

        // Eliminar con respuesta que tampoco están en template
        foreach ($currentRecords->where('result', '!=', 0) as $record) {
          if (!$templateKeys->has($record->competence_id . '_' . $record->sub_competence_id)) {
            $record->delete();
            $deletedWithResponse++;
          }
        }

        // Agregar registros faltantes del template
        $added = $this->agregarCompetenciasFaltantes($evaluation, $persona, $templateCompetencies);

        $deleted = $deletedNoResponse + $deletedWithResponse;

        if ($deleted > 0 || $added > 0) {
          $this->recalculatePersonResults($evaluationId, $persona->id);
        }

        $stats['personas_procesadas']++;
        $stats['registros_eliminados'] += $deleted;
        $stats['registros_sin_respuesta_eliminados'] += $deletedNoResponse;
        $stats['registros_con_respuesta_eliminados'] += $deletedWithResponse;
        $stats['registros_agregados'] += $added;
        $stats['personas'][] = [
          'person_id' => $persona->id,
          'name' => $persona->nombre_completo,
          'eliminados' => $deleted,
          'eliminados_sin_respuesta' => $deletedNoResponse,
          'eliminados_con_respuesta' => $deletedWithResponse,
          'agregados' => $added,
        ];
      }

      return $stats;
    });
  }

  /**
   * Calcula (sin crear) qué registros habría que agregar para una persona.
   */
  private function calcularRegistrosAgregar(Evaluation $evaluation, Worker $persona, array $templateCompetencies): array
  {
    $toAdd = [];

    foreach ($templateCompetencies as $competenciaData) {
      $existingCombos = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluation->id)
        ->where('person_id', $persona->id)
        ->where('competence_id', $competenciaData['competence_id'])
        ->where('sub_competence_id', $competenciaData['sub_competence_id'])
        ->get()
        ->mapWithKeys(fn($r) => [$r->evaluator_id . '_' . $r->evaluatorType => true]);

      foreach ($this->buildCandidates($evaluation, $persona) as $candidate) {
        $comboKey = $candidate['evaluator_id'] . '_' . $candidate['type'];
        if (!$existingCombos->has($comboKey)) {
          $toAdd[] = [
            'competence' => $competenciaData['competence_name'],
            'sub_competence' => $competenciaData['sub_competence_name'],
            'evaluatorType' => $candidate['type'],
            'evaluator_id' => $candidate['evaluator_id'],
          ];
        }
      }
    }

    return $toAdd;
  }

  /**
   * Agrega los registros de competencia faltantes para una persona según el template.
   * Solo crea los combos (competencia+subcompetencia+evaluador) que aún no existen.
   */
  private function agregarCompetenciasFaltantes(Evaluation $evaluation, Worker $persona, array $templateCompetencies): int
  {
    $added = 0;
    $candidates = $this->buildCandidates($evaluation, $persona);

    foreach ($templateCompetencies as $competenciaData) {
      $existingCombos = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluation->id)
        ->where('person_id', $persona->id)
        ->where('competence_id', $competenciaData['competence_id'])
        ->where('sub_competence_id', $competenciaData['sub_competence_id'])
        ->get()
        ->mapWithKeys(fn($r) => [$r->evaluator_id . '_' . $r->evaluatorType => true]);

      foreach ($candidates as $candidate) {
        $comboKey = $candidate['evaluator_id'] . '_' . $candidate['type'];
        if (!$existingCombos->has($comboKey)) {
          $this->crearDetalleCompetencia(
            $evaluation->id,
            $persona,
            $competenciaData,
            $candidate['evaluator_id'],
            $candidate['type']
          );
          $added++;
        }
      }
    }

    return $added;
  }

  /**
   * Construye la lista de evaluadores requeridos según tipo de evaluación.
   */
  private function buildCandidates(Evaluation $evaluation, Worker $persona): array
  {
    $candidates = [];

    if ($evaluation->typeEvaluation == self::EVALUACION_180) {
      if ($persona->jefe_id) {
        $candidates[] = ['evaluator_id' => $persona->jefe_id, 'type' => self::TIPO_EVALUADOR_JEFE];
      }
    } else {
      if ($evaluation->selfEvaluation) {
        $candidates[] = ['evaluator_id' => $persona->id, 'type' => self::TIPO_EVALUADOR_AUTOEVALUACION];
      }
      if ($persona->jefe_id) {
        $candidates[] = ['evaluator_id' => $persona->jefe_id, 'type' => self::TIPO_EVALUADOR_JEFE];
      }
      if ($evaluation->partnersEvaluation && $persona->jefe_id) {
        foreach ($this->obtenerCompaneros($persona) as $companero) {
          $candidates[] = ['evaluator_id' => $companero->id, 'type' => self::TIPO_EVALUADOR_COMPANEROS];
        }
      }
      foreach ($this->obtenerSubordinados($persona->id) as $subordinado) {
        $candidates[] = ['evaluator_id' => $subordinado->id, 'type' => self::TIPO_EVALUADOR_REPORTES];
      }
    }

    return $candidates;
  }
}
