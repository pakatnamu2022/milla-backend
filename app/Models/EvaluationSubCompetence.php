<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationSubCompetence extends BaseModel
{
    protected $table = 'gh_config_subcompetencias';

    protected $fillable = [
        'competencia_id',
        'nombre',
        'definicion',
        'status_delete',
    ];

    public function competence(): BelongsTo
    {
        return $this->belongsTo(EvaluationCompetence::class, 'competencia_id');
    }
}
