<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetailResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionsistema\Person;
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

    // Actualizar EvaluationPerson si existe
    $this->updateEvaluationPerson($evaluationId, $personId, $competencesResult, $objectivesResult);
  }

  /**
   * Calcular resultado de competencias según el tipo de evaluación
   */
  public function calculateCompetencesResult($evaluationId, $personId, $evaluationType)
  {
    $competences = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->get();

    if ($competences->isEmpty()) {
      return 0;
    }

    switch ($evaluationType) {
      case self::EVALUACION_180:
        // Para 180°, solo considerar evaluación del jefe
        $jefeCompetences = $competences->where('evaluatorType', self::TIPO_EVALUADOR_JEFE);
        return $jefeCompetences->avg('result') ?? 0;

      case self::EVALUACION_360:
        // Para 360°, calcular promedio ponderado por tipo de evaluador
        return $this->calculate360CompetencesResult($competences);

      default:
        // Para evaluación de objetivos, promedio simple
        return $competences->avg('result') ?? 0;
    }
  }

  /**
   * Calcular resultado de competencias para evaluación 360°
   */
  private function calculate360CompetencesResult($competences)
  {
    // Pesos por tipo de evaluador (esto puede ser configurable)
    $weights = [
      self::TIPO_EVALUADOR_JEFE => 0.4,          // 40% jefe
      self::TIPO_EVALUADOR_AUTOEVALUACION => 0.2, // 20% autoevaluación
      self::TIPO_EVALUADOR_COMPANEROS => 0.25,    // 25% compañeros
      self::TIPO_EVALUADOR_REPORTES => 0.15       // 15% reportes
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
   * Calcular resultado de objetivos
   */
  private function calculateObjectivesResult($evaluationId, $personId)
  {
    // Obtener objetivos de EvaluationPerson para esta evaluación y persona
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->get();

    if ($evaluationPersons->isEmpty()) {
      return 0;
    }

    // Calcular promedio ponderado por peso de cada objetivo
    $totalWeightedScore = 0;
    $totalWeight = 0;

    foreach ($evaluationPersons as $evaluationPerson) {
      $weight = $evaluationPerson->personCycleDetail->weight ?? 0;
      $result = $evaluationPerson->result ?? 0;

      $totalWeightedScore += $result * $weight;
      $totalWeight += $weight;
    }

    return $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
  }

  /**
   * Actualizar EvaluationPersonResult
   */
  private function updatePersonResult($evaluationId, $personId, $competencesResult, $objectivesResult)
  {
    $personResult = EvaluationPersonResult::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->first();

    if ($personResult) {
      // Calcular resultado final basado en porcentajes de la evaluación
      $evaluation = Evaluation::find($evaluationId);
      $competencesPercentage = $evaluation->competencesPercentage / 100;
      $objectivesPercentage = $evaluation->objectivesPercentage / 100;

      $finalResult = ($competencesResult * $competencesPercentage) + ($objectivesResult * $objectivesPercentage);

      $personResult->update([
        'competencesResult' => $competencesResult,
        'objectivesResult' => $objectivesResult,
        'result' => $finalResult
      ]);
    }
  }

  /**
   * Actualizar EvaluationPerson
   */
  private function updateEvaluationPerson($evaluationId, $personId, $competencesResult, $objectivesResult)
  {
    // Actualizar todos los registros de EvaluationPerson para esta evaluación y persona
    EvaluationPerson::where('evaluation_id', $evaluationId)
      ->where('person_id', $personId)
      ->update([
        'result' => $competencesResult // o el resultado que corresponda según tu lógica
      ]);
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
        $persona = Person::find($personaResultado->person_id);

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
    $evaluador = Person::find($evaluadorId);

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
      ->where('gh_config_competencias.status_delete', 0)
      ->where('gh_config_subcompetencias.status_delete', 0)
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
    return Person::where('jefe_id', $personaId)
      ->where('status_deleted', 1)
      ->where('status_id', 22) // Activo según tu constante WORKER_ACTIVE
      ->exists();
  }

  /**
   * Obtener subordinados directos de una persona
   */
  private function obtenerSubordinados($personaId)
  {
    return Person::where('jefe_id', $personaId)
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

    return Person::where('jefe_id', $persona->jefe_id)
      ->where('id', '!=', $persona->id)
      ->where('status_deleted', 1)
      ->where('status_id', 22)
      ->get();
  }
}
