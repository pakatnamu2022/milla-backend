<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationSubCompetence extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_config_subcompetencias';

  protected $fillable = [
    'competencia_id',
    'nombre',
    'definicion',
    'level1',
    'level2',
    'level3',
    'level4',
    'level5',
  ];

  public function competence(): BelongsTo
  {
    return $this->belongsTo(EvaluationCompetence::class, 'competencia_id');
  }
}
