<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCompetence extends BaseModel
{
  use SoftDeletes;

  protected $table = 'gh_config_competencias';

  protected $fillable = [
    'nombre',
  ];

  const filters = [
    'search' => ['nombre'],
  ];

  const sorts = [
    'id' => 'id',
    'nombre' => 'nombre',
  ];


  public function subCompetences(): HasMany
  {
    return $this->hasMany(EvaluationSubCompetence::class, 'competencia_id');
  }
}
