<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCompetence extends BaseModel
{
    protected $table = 'gh_config_competencias';

    protected $fillable = [
        'nombre',
        'grupo_cargos_id',
        'status_delete',
    ];

    const filters = [
        'search' => ['nombre'],
        'grupo_cargos_id' => '=',
    ];

    const sorts = [
        'id' => 'id',
        'nombre' => 'nombre',
        'grupo_cargos_id' => 'grupo_cargos_id',
    ];


    public function subCompetences(): HasMany
    {
        return $this->hasMany(EvaluationSubCompetence::class, 'competencia_id')
            ->where('status_delete', 0);
    }
}
