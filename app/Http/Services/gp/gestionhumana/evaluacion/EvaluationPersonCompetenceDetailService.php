<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionsistema\Person;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;

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
          'tipo_evaluacion' => $evaluacion->tipo_evaluacion_texto,
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
    // Aquí debes implementar la lógica para obtener las competencias
    // basándote en la categoría jerárquica de la persona usando tu sistema existente

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

  /**
   * Obtener resumen de la evaluación creada
   */
  public function obtenerResumenEvaluacion($evaluacionId)
  {
    $evaluacion = Evaluation::findOrFail($evaluacionId);

    $totalPersonas = EvaluationPersonResult::where('evaluation_id', $evaluacionId)->count();

    $totalCompetencias = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluacionId)->count();

    $competenciasPorTipo = EvaluationPersonCompetenceDetail::where('evaluation_id', $evaluacionId)
      ->selectRaw('evaluatorType, count(*) as total')
      ->groupBy('evaluatorType')
      ->get()
      ->mapWithKeys(function ($item) {
        $tipos = [
          0 => 'Líder Directo',
          1 => 'Autoevaluación',
          2 => 'Compañeros',
          3 => 'Reportes'
        ];
        return [$tipos[$item->evaluatorType] => $item->total];
      });

    return [
      'evaluacion' => [
        'id' => $evaluacion->id,
        'nombre' => $evaluacion->name,
        'tipo' => $evaluacion->tipo_evaluacion_texto,
        'estado' => $evaluacion->estado_texto,
        'fecha_inicio' => $evaluacion->start_date,
        'fecha_fin' => $evaluacion->end_date
      ],
      'estadisticas' => [
        'total_personas' => $totalPersonas,
        'total_competencias' => $totalCompetencias,
        'competencias_por_tipo' => $competenciasPorTipo
      ],
      'configuracion' => [
        'autoevaluacion' => $evaluacion->selfEvaluation,
        'evaluacion_companeros' => $evaluacion->partnersEvaluation,
        'porcentaje_objetivos' => $evaluacion->objectivesPercentage,
        'porcentaje_competencias' => $evaluacion->competencesPercentage
      ]
    ];
  }

  /**
   * Validar que una evaluación esté lista para procesar competencias
   */
  public function validarEvaluacion($evaluacionId)
  {
    try {
      $evaluacion = Evaluation::findOrFail($evaluacionId);

      // Verificar que sea 180° o 360°
      if (!in_array($evaluacion->typeEvaluation, [self::EVALUACION_180, self::EVALUACION_360])) {
        return [
          'success' => false,
          'message' => 'La evaluación debe ser de tipo 180° o 360°'
        ];
      }

      // Verificar que tenga personas en results
      $totalPersonas = EvaluationPersonResult::where('evaluation_id', $evaluacionId)->count();

      if ($totalPersonas == 0) {
        return [
          'success' => false,
          'message' => 'La evaluación no tiene personas asignadas en results'
        ];
      }

      // Verificar que las personas tengan jefe asignado
      $personasSinJefe = DB::table('gh_evaluation_person_result')
        ->join('rrhh_persona', 'gh_evaluation_person_result.person_id', '=', 'rrhh_persona.id')
        ->where('gh_evaluation_person_result.evaluation_id', $evaluacionId)
        ->whereNull('rrhh_persona.jefe_id')
        ->count();

      if ($personasSinJefe > 0) {
        return [
          'success' => false,
          'message' => "Hay {$personasSinJefe} personas sin jefe asignado"
        ];
      }

      return [
        'success' => true,
        'message' => 'La evaluación está lista para procesar competencias',
        'data' => [
          'total_personas' => $totalPersonas,
          'tipo_evaluacion' => $evaluacion->tipo_evaluacion_texto,
          'autoevaluacion' => $evaluacion->selfEvaluation,
          'evaluacion_companeros' => $evaluacion->partnersEvaluation
        ]
      ];

    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => 'Error al validar evaluación: ' . $e->getMessage()
      ];
    }
  }
}
