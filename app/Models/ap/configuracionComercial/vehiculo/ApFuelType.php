<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApFuelType extends Model
{
    use SoftDeletes;

    protected $table = 'ap_tipo_combustible';

    protected $fillable = [
        'id',
        'codigo',
        'descripcion',
        'motor_electrico',
    ];

    const filters = [
        'search' => ['codigo', 'descripcion'],
        'codigo' => 'like',
        'descripcion' => 'like',
    ];

    const sorts = [
        'id',
        'codigo',
        'descripcion',
    ];
}
