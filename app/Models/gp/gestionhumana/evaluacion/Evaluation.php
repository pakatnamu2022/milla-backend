<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'gh_evaluation';

  protected $fillable = [
    'name', 'start_date', 'end_date', 'status', 'selfEvaluation',
    'partnersEvaluation', 'typeEvaluation', 'objectivesPercentage',
    'competencesPercentage', 'cycle_id', 'period_id',
    'competence_parameter_id', 'objective_parameter_id', 'final_parameter_id'
  ];

  protected $casts = [
    'selfEvaluation' => 'boolean',
    'partnersEvaluation' => 'boolean',
    'objectivesPercentage' => 'decimal:2',
    'competencesPercentage' => 'decimal:2',
    'start_date' => 'date',
    'end_date' => 'date'
  ];

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
}
