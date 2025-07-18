<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationPeriod extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_evaluation_periods';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    const filters = [
        'name' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    const sorts = [
        'name' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];


}
