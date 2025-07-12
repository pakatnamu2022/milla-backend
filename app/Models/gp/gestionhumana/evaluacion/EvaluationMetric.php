<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use App\Models\BaseModel;

class EvaluationMetric extends BaseModel
{
    protected $table = "gh_metrica_objetivos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'status_deleted',
    ];

    const filters = [
        'id' => '=',
        'search' => ['nombre', 'descripcion'],
        'status_deleted' => '='
    ];

    const sorts = [
        'id',
        'nombre',
        'descripcion',
        'status_deleted'
    ];

}
