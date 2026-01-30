<?php

namespace App\Models\tp\comercial;

use Illuminate\Database\Eloquent\Model;

class OpGoalTravel extends Model
{
    protected $table = 'op_meta_viajes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'fecha',
        'total',
        'meta_conductor',
        'meta_vehiculo',
        'total_unidades',
        'status_deleted'
    ];

    const filters = [
        'id' => '=',
        'fecha' => '=',
        'total' => '=',
        'meta_conductor' => '=',
        'meta_vehiculo' => '=',
        'total_unidades' => '=',
    ];

    const sorts = [
        'id' => 'asc',
        'fecha' => 'asc',
        'meta_conductor' => 'asc',
        'meta_vehiculo' => 'asc',
        'total_unidades' => 'asc'
    ];
}
