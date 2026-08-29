<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EvaluationCategoryObjectiveDetail extends Model
{
  use SoftDeletes, Reportable;

  protected $table = 'gh_evaluation_category_objective';

  protected $fillable = [
    'objective_id',
    'category_id',
    'person_id',
    'goal',
    'weight',
    'fixedWeight',
    'active',
  ];

  const filters = [
    'id'           => '=',
    'objective_id' => '=',
    'category_id'  => '=',
    'person_id'    => '=',
    'goal'         => '=',
    'weight'       => '=',
    'active'       => '=',
  ];

  const sorts = [
    'id',
    'objective_id',
    'category_id',
    'goal',
    'weight',
  ];

  protected $casts = [
    'fixedWeight' => 'boolean',
  ];

  protected $reportRelations = ['worker.position', 'worker.area', 'worker.boss', 'objective.metric', 'category'];

  protected $reportColumns = [
    'worker.vat'                  => ['label' => 'DNI', 'width' => 12],
    'worker.nombre_completo'      => ['label' => 'Nombre Completo', 'width' => 30],
    'worker.position.name'        => ['label' => 'Puesto', 'width' => 25],
    'worker.area.name'            => ['label' => 'Área', 'width' => 20],
    'worker.boss.nombre_completo' => ['label' => 'Líder', 'width' => 30],
    'category.name'               => ['label' => 'Categoría', 'width' => 25],
    'objective.name'              => ['label' => 'Objetivo', 'width' => 35],
    'descripcion_report'          => ['label' => 'Descripción', 'accessor' => 'getDescripcionReportAttribute', 'width' => 35],
    'objective.metric.name'       => ['label' => 'Métrica', 'width' => 18],
    'goal'                        => ['label' => 'Meta', 'width' => 10],
    'logica_report'               => ['label' => 'Lógica', 'accessor' => 'getLogicaReportAttribute', 'width' => 18],
    'weight'                      => ['label' => 'Peso', 'width' => 14],
  ];

  public function getDescripcionReportAttribute(): string
  {
    return Str::limit($this->objective?->description ?? '', 200, '...');
  }

  public function getLogicaReportAttribute(): string
  {
    return $this->objective?->isAscending ? 'Ascendente' : 'Descendente';
  }

  public function getReportData($filters = [], $columns = null)
  {
    return $this->newQuery()
      ->with(['worker.position', 'worker.area', 'worker.boss', 'objective.metric', 'category'])
      ->where('active', 1)
      ->whereHas('objective', fn($q) => $q->where('active', 1))
      ->whereNull('deleted_at')
      ->orderBy('person_id')
      ->get();
  }

  public function objective()
  {
    return $this->belongsTo(EvaluationObjective::class, 'objective_id');
  }

  public function category()
  {
    return $this->belongsTo(HierarchicalCategory::class, 'category_id');
  }

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'person_id');
  }
}
