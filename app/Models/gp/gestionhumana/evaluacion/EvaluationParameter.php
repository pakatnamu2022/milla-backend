<?php

namespace App\Models\gp\gestionhumana\evaluacion;

use Illuminate\Database\Eloquent\Model;

class EvaluationParameter extends Model
{
    protected $table = 'gh_evaluation_parameter';

    protected $fillable = [
        'name',
        'type',
        'isPercentage',
    ];

    public const filters = [
        'search' => ['name', 'type'],
        'name' => 'like',
        'type' => 'exact',
    ];

    public const sorts = [
        'name',
        'type',
    ];

    public function details()
    {
        return $this->hasMany(EvaluationParameterDetail::class, 'parameter_id', 'id');
    }


}
