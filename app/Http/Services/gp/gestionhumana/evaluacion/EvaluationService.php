<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationService extends BaseService
{
  protected EvaluationPersonService $evaluationPersonService;
  protected EvaluationPersonResultService $evaluationPersonResultService;

  // Tipos de evaluador
  const TIPO_EVALUADOR_JEFE = 0;           // Líder directo
  const TIPO_EVALUADOR_AUTOEVALUACION = 1; // Autoevaluación
  const TIPO_EVALUADOR_COMPANEROS = 2;     // Compañeros
  const TIPO_EVALUADOR_REPORTES = 3;       // Reportes/Subordinados

  // Tipos de evaluación
  const EVALUACION_OBJETIVOS = 0;
  const EVALUACION_180 = 1;
  const EVALUACION_360 = 2;

  public function __construct(
    EvaluationPersonService       $evaluationPersonService,
    EvaluationPersonResultService $evaluationPersonResultService
  )
  {
    $this->evaluationPersonService = $evaluationPersonService;
    $this->evaluationPersonResultService = $evaluationPersonResultService;
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
    $persons = Person::whereIn('id', $personsInCycle)->get();
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
    $positionsIds = Person::whereIn('id', $personsInCycle)->select('cargo_id')->distinct()->get()->pluck('cargo_id')->toArray();
    $positions = Position::whereIn('id', $positionsIds)->get();
    return PositionResource::collection($positions);
  }

  public function enrichData($data)
  {
    $cycle = EvaluationCycle::find($data['cycle_id']);
    $data['typeEvaluation'] = $cycle->typeEvaluation;
    $data['objective_parameter_id'] = $cycle->parameter_id;
    $data['period_id'] = $cycle->period_id;
    if ($data['typeEvaluation'] == self::EVALUACION_360) {
      $data['selfEvaluation'] = 1;
      $data['partnersEvaluation'] = 1;
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
          // Log del error pero no fallar la creación de la evaluación
          \Log::warning('Error al crear competencias automáticamente', [
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
        $partners = $this->obtenerCompaneros($persona);
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

  public function show($id)
  {
    return new EvaluationResource($this->find($id));
  }

  public function regenerateEvaluation($evaluationId)
  {
    $evaluation = $this->find($evaluationId);

    // Verificar si hay cambios en las personas del ciclo
    $needsRegeneration = $this->checkCycleChanges($evaluation);

    if ($needsRegeneration) {
      // Regenerar personas del ciclo actualizado
      $this->evaluationPersonResultService->storeMany($evaluation->id);
      $this->evaluationPersonService->storeMany($evaluation->id);

      // Regenerar competencias si es evaluación 180° o 360°
      if (in_array($evaluation->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        $competencesResult = $this->crearCompetenciasEvaluacion($evaluation);

        if (!$competencesResult['success']) {
          \Log::warning('Error al regenerar competencias tras cambios en ciclo', [
            'evaluation_id' => $evaluation->id,
            'error' => $competencesResult['message']
          ]);
        }
      }
    }

    return [
      'message' => $needsRegeneration ? 'Evaluación regenerada con éxito' : 'No se detectaron cambios en las personas del ciclo; no se realizaron modificaciones'
    ];
  }

  public function update($data)
  {
    DB::beginTransaction();

    try {
      $evaluation = $this->find($data['id']);
      $data = $this->enrichData($data);
      $evaluation->update($data);

      $this->regenerateEvaluation($evaluation->id);

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
      ->where('p.status_id', 22);

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
