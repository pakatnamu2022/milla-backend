<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationSubCompetence extends BaseModel
{
    protected $table = 'gh_config_subcompetencias';

    protected $fillable = [
        'competencia_id',
        'nombre',
        'definicion',
        'status_delete',
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
