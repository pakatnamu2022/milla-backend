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

    protected $casts = [
        'fecha' => 'date',
    ];

    const filters = [
        'search' => ['meta_conductor', 'meta_vehiculo', 'total'],
        'status_deleted' => '=',
        'fecha' => '=',
        'meta_conductor' => '=',
        'meta_vehiculo' => '=',
        'total' => '=',
        'total_unidades' => '=',
        'year' => 'accessor',
        'month' => 'accessor',
    ];

    const sorts = [
        'id',
        'fecha',
        'meta_conductor',
        'meta_vehiculo',
        'total_unidades',
        'total',
        'created_at',
        'updated_at'
    ];

    public function getYearAttribute()
    {
        return $this->fecha ? $this->fecha->year : null;
    }
    public function getMonthAttribute()
    {
        return $this->fecha ? $this->fecha->month : null;
    }
}
