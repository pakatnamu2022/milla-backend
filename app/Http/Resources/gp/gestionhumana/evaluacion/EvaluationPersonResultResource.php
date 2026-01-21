<?php

// Mejoras adicionales para tu Resource

namespace App\Http\Resources\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPersonResultResource extends JsonResource
{
  protected $showExtra = false;

  public function showExtra($show = true)
  {
    $this->showExtra = $show;
    return $this;
  }

  public function toArray(Request $request): array
  {
    $response = [
      'id' => $this->id,
      'person_id' => $this->person_id,
      'evaluation_id' => $this->evaluation_id,
      'supervisor_id' => $this->person?->supervisor_id,
      'supervisor' => $this->person?->supervisor ? new WorkerResource($this->person?->supervisor) : null,
      'person' => $this->showExtra ? (new WorkerResource($this->person))->showExtra() : new WorkerResource($this->person),
      'competencesPercentage' => round($this->competencesPercentage, 2),
      'objectivesPercentage' => round($this->objectivesPercentage, 2),
      'objectivesResult' => round($this->objectivesResult, 2),
      'competencesResult' => round($this->competencesResult, 2),
      'result' => round($this->result, 2),
      'total_progress' => $this->total_progress,
      'is_completed' => $this->is_completed,
//      'completion_percentage' => round($this->completion_percentage, 2),
    ];

    if ($this->showExtra) {
      $response['evaluation'] = new EvaluationResource($this->evaluation);
      $response['details'] = EvaluationPersonResource::collection($this->details);
      $response['competenceGroups'] = $this->getGroupedCompetences();
      $response['statistics'] = $this->getEvaluationStatistics();
      $response['maxFinalParameter'] = round((new EvaluationParameterResource($this->evaluation->finalParameter))->details->last()->to, 2);
      $response['maxObjectiveParameter'] = round((new EvaluationParameterResource($this->evaluation->objectiveParameter))->details->last()->to, 2);
      $response['maxCompetenceParameter'] = round((new EvaluationParameterResource($this->evaluation->competenceParameter))->details->last()->to, 2);
      $response['finalParameter'] = new EvaluationParameterResource($this->evaluation->finalParameter);
      $response['objectiveParameter'] = new EvaluationParameterResource($this->evaluation->objectiveParameter);
      $response['competenceParameter'] = new EvaluationParameterResource($this->evaluation->competenceParameter);
      $response['hasObjectives'] = (bool)$this->person->position->hierarchicalCategory->hasObjectives;
    }

    return $response;
  }

  /**
   * Obtiene estadísticas detalladas de la evaluación
   */
  private function getEvaluationStatistics()
  {
    $competenceGroups = $this->getGroupedCompetences();
    $objectives = $this->details;

    // Estadísticas de competencias
    $totalSubCompetences = collect($competenceGroups)->sum('total_sub_competences');
    $completedSubCompetences = collect($competenceGroups)->sum('completed_evaluations');
    $competenceCompletionRate = $totalSubCompetences > 0 ?
      round(($completedSubCompetences / $totalSubCompetences) * 100, 2) : 0;

    // Estadísticas de objetivos
    $totalObjectives = $objectives->count();
    $completedObjectives = $objectives->where('wasEvaluated', 1)->count();
    $objectiveCompletionRate = $totalObjectives > 0 ?
      round(($completedObjectives / $totalObjectives) * 100, 2) : 0;

    // Promedio por tipo de evaluador (solo para 360°)
    $evaluatorAverages = [];
    if ($this->evaluation->typeEvaluation == 2) { // 360°
      $evaluatorAverages = $this->getEvaluatorAverages($competenceGroups);
    }

    // Análisis de brechas (competencias con menor puntuación)
    $competenceAnalysis = $this->getCompetenceAnalysis($competenceGroups);


    return [
      'overall_completion_rate' => round(($competenceCompletionRate + $objectiveCompletionRate) / (
        max(($totalSubCompetences > 0 ? 1 : 0) + ($totalObjectives > 0 ? 1 : 0), 1)
        ), 2),
      'competences' => [
        'index_range_result' => $this->calculateIndexRangeResult(round(collect($competenceGroups)->avg('average_result'), 2), $this->evaluation->competenceParameter),
        'label_range' => $this->calculateLabelRangeResult(round(collect($competenceGroups)->avg('average_result'), 2), $this->evaluation->competenceParameter),
        'completion_rate' => $competenceCompletionRate,
        'completed' => $completedSubCompetences,
        'total' => $totalSubCompetences,
        'average_score' => round(collect($competenceGroups)->avg('average_result'), 2),
        'max_score' => round((new EvaluationParameterResource($this->evaluation->competenceParameter))->details->last()->to, 2),
      ],
      'objectives' => [
        'index_range_result' => $this->person->position->hierarchicalCategory->hasObjectives ? $this->calculateIndexRangeResult($this->objectivesResult, $this->evaluation->objectiveParameter) : $this->evaluation->objectiveParameter->details->count() - 1,
        'label_range' => $this->person->position->hierarchicalCategory->hasObjectives ? $this->calculateLabelRangeResult($this->objectivesResult, $this->evaluation->objectiveParameter) : $this->evaluation->objectiveParameter->details->last()->label,
        'completion_rate' => $objectiveCompletionRate,
        'completed' => $completedObjectives,
        'total' => $totalObjectives,
        'average_score' => round($objectives->where('result', '>', 0)->avg('result'), 2),
        'max_score' => $this->person->position->hierarchicalCategory->hasObjectives ? round((new EvaluationParameterResource($this->evaluation->objectiveParameter))->details->last()->to, 2) : 0,
      ],
      'final' => [
        'index_range_result' => $this->calculateIndexRangeResult($this->result, $this->evaluation->finalParameter),
        'label_range' => $this->calculateLabelRangeResult($this->result, $this->evaluation->finalParameter),
        'max_score' => round((new EvaluationParameterResource($this->evaluation->finalParameter))->details->last()->to, 2),
      ],
      'evaluator_averages' => $evaluatorAverages,
      'competence_analysis' => $competenceAnalysis,
      'evaluation_status' => $this->getEvaluationStatus(),
    ];
  }


  private function calculateIndexRangeResult($percentage, $parameter)
  {
    $orderedDetailsByTo = $parameter->details->sortBy('to');
    foreach ($orderedDetailsByTo as $index => $detail) {
      if ($percentage < $detail->to) {
        return $index;
      }
    }
    return $orderedDetailsByTo->count() - 1; // Si es mayor que todos, retorna el último índice
  }

  private function calculateLabelRangeResult($percentage, $parameter)
  {
    $orderedDetailsByTo = $parameter->details->sortBy('to');
    foreach ($orderedDetailsByTo as $detail) {
      if ($percentage < $detail->to) {
        return $detail->label;
      }
    }
    return $orderedDetailsByTo->last()->label; // Si es mayor que todos, retorna la última etiqueta
  }

  /**
   * Obtiene promedios por tipo de evaluador
   */
  private function getEvaluatorAverages($competenceGroups)
  {
    $evaluatorTypes = [
      0 => 'Jefe Directo',
      1 => 'Pares',
      2 => 'Subordinados',
      3 => 'Autoevaluación'
    ];

    $averages = [];

    foreach ($evaluatorTypes as $type => $name) {
      $scores = [];

      foreach ($competenceGroups as $group) {
        foreach ($group['sub_competences'] as $subCompetence) {
          $evaluator = collect($subCompetence['evaluators'])
            ->firstWhere('evaluator_type', $type);

          if ($evaluator && $evaluator['is_completed']) {
            $scores[] = floatval($evaluator['result']);
          }
        }
      }

      if (!empty($scores)) {
        $averages[] = [
          'evaluator_type' => $type,
          'evaluator_type_name' => $name,
          'average_score' => round(array_sum($scores) / count($scores), 2),
          'total_evaluations' => count($scores),
        ];
      }
    }

    return $averages;
  }

  /**
   * Análisis de competencias (fortalezas y oportunidades)
   */
  private function getCompetenceAnalysis($competenceGroups)
  {
    $competences = collect($competenceGroups)->map(function ($group) {
      return [
        'competence_name' => $group['competence_name'],
        'average_result' => $group['average_result'],
        'completion_rate' => $group['completed_evaluations'] > 0 ?
          round(($group['completed_evaluations'] / $group['total_sub_competences']) * 100, 2) : 0,
      ];
    })->sortByDesc('average_result');

    return [
      'strengths' => $competences->take(3)->values()->toArray(), // Top 3
      'opportunities' => $competences->reverse()->take(3)->values()->toArray(), // Bottom 3
    ];
  }

  /**
   * Estado detallado de la evaluación
   */
  private function getEvaluationStatus()
  {
    $now = now();
    $startDate = $this->evaluation->start_date;
    $endDate = $this->evaluation->end_date;

    $status = [
      'current_status' => $this->evaluation->status,
      'status_name' => $this->evaluation->statusName,
      'is_active' => $now->between($startDate, $endDate),
      'days_remaining' => $now < $endDate ? $now->diffInDays($endDate) : 0,
      'is_overdue' => $now > $endDate && $this->evaluation->status != 2, // No completado y vencido
    ];

    // Calcular tiempo transcurrido
    $totalDays = \Carbon\Carbon::parse($startDate)->diffInDays($endDate);
    $daysPassed = \Carbon\Carbon::parse($startDate)->diffInDays($now);
    $status['progress_percentage'] = $totalDays > 0 ?
      min(round(($daysPassed / $totalDays) * 100, 2), 100) : 100;

    return $status;
  }

  /**
   * Agrupa las competencias por competencia principal (método existente mejorado)
   */
  private function getGroupedCompetences()
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

        $evaluators = [];

        // Listar TODOS los evaluadores individuales
        foreach ($evaluations as $evaluation) {
          $evaluators[] = [
            'evaluator_type' => $evaluation->evaluatorType,
            'evaluator_type_name' => $this->getEvaluatorTypeName($evaluation->evaluatorType),
            'evaluator_id' => $evaluation->evaluator_id,
            'evaluator_name' => $evaluation->evaluator ?? 'Pendiente',
            'result' => round($evaluation->result, 2),
            'id' => $evaluation->id,
            'is_completed' => floatval($evaluation->result) > 0,
          ];
        }

        // Calcular promedio ponderado por tipo de evaluador
        $evaluationsByType = $evaluations->groupBy('evaluatorType');
        $totalScore = 0;
        $validEvaluations = 0;

        foreach ($evaluationsByType as $evaluatorType => $typeEvaluations) {
          $completedEvaluations = $typeEvaluations->filter(function ($eval) {
            return floatval($eval->result) > 0;
          });

          if ($completedEvaluations->isNotEmpty()) {
            $typeAverage = $completedEvaluations->avg('result');
            $totalScore += $typeAverage;
            $validEvaluations++;
          }
        }

        $averageScore = $validEvaluations > 0 ? $totalScore / $validEvaluations : 0;

        // Calcular completion basándose en los tipos únicos que existen
        $uniqueEvaluatorTypes = $evaluations->pluck('evaluatorType')->unique()->count();
        $completedTypes = $evaluationsByType->filter(function ($typeEvals) {
          return $typeEvals->filter(fn($e) => floatval($e->result) > 0)->isNotEmpty();
        })->count();

        $processedSubCompetences[] = [
          'sub_competence_id' => $subCompetenceId,
          'sub_competence_name' => $firstEvaluation->sub_competence,
          'sub_competence_description' => $firstEvaluation->subCompetence?->definicion ?? '',
          'evaluators' => $evaluators,
          'average_result' => round($averageScore, 2),
          'completion_percentage' => $uniqueEvaluatorTypes > 0 ? ($completedTypes / $uniqueEvaluatorTypes) * 100 : 0,
          'is_completed' => $completedTypes === $uniqueEvaluatorTypes,
        ];
      }

      $competenceAverage = collect($processedSubCompetences)->avg('average_result');
      $completedSubCompetences = collect($processedSubCompetences)->where('is_completed', true)->count();

      $groupedCompetences[] = [
        'competence_id' => $competenceId,
        'competence_name' => $firstDetail->competence,
        'competence_description' => $this->extractCompetenceDescription($firstDetail->competence),
        'sub_competences' => $processedSubCompetences,
        'average_result' => round($competenceAverage, 2),
        'total_sub_competences' => count($processedSubCompetences),
        'completed_evaluations' => $completedSubCompetences,
        'evaluation_type' => $evaluationType,
        'evaluation_type_name' => $this->evaluation->typeEvaluationName,
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
      $types = [0, 1]; // Jefe directo + Autoevaluación
      $types[] = 2; // Pares

      if ($this->hasSubordinates()) {
        $types[] = 3; // Reportes
      }

      return $types;
    }
  }

  /**
   * Verifica si la persona tiene subordinados
   */
  private function hasSubordinates()
  {
    // Implementa aquí la lógica para verificar si tiene subordinados
    return $this->person->subordinates()->count() > 0;
  }

  /**
   * Extrae la descripción de la competencia
   */
  private function extractCompetenceDescription($competenceFull)
  {
    $parts = explode(':', $competenceFull, 2);
    return count($parts) > 1 ? trim($parts[1]) : '';
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
}

