<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApGearShiftType extends Model
{
    use SoftDeletes;

    protected $table = 'ap_tipo_cambio_marcha';

    protected $fillable = [
        'id',
        'codigo',
        'descripcion',
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
