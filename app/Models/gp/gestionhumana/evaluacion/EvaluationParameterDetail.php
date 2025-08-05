<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;

class EvaluationParameterDetail extends Model
{
    protected $table = 'gh_evaluation_parameter_detail';

    protected $fillable = [
        'label',
        'from',
        'to',
        'parameter_id',
    ];

    public const filters = [
        'search' => ['label', 'from', 'to'],
        'label' => 'like',
        'from' => '=',
        'to' => '=',
        'parameter_id' => '=',
    ];

    public const sorts = [
        'label',
        'from',
        'to',
        'parameter_id',
    ];

    public function parameter()
    {
        return $this->belongsTo(EvaluationParameter::class, 'parameter_id', 'id');
    }
}
