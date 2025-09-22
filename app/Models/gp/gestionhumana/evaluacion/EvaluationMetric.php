<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationMetric extends BaseModel
{
  use SoftDeletes, Reportable;

  protected $table = "gh_evaluation_metric";
  protected $primaryKey = 'id';

  protected $fillable = [
    'name',
    'description',
  ];

  const filters = [
    'id' => '=',
    'search' => ['name', 'description'],
  ];

  const sorts = [
    'id',
    'name',
    'description',
  ];

  protected $reportColumns = [
    'name' => [
      'label' => 'Nombre',
      'formatter' => null,
      'width' => 50
    ],
    'description' => [
      'label' => 'Descripción',
      'formatter' => null,
      'width' => 50
    ],
  ];


}
