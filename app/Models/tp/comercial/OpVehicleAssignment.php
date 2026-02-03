<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\Driver;

class OpVehicleAssignment extends BaseModel
{
    protected $table = 'com_asig_vehiculo_conductor';
    protected $primaryKey = 'id';

    protected $fillable = [
        'tracto_id',
        'conductor_id',
        'status_deleted'
    ];

    const filters = [
        'id' => '=',
        'search' => [
            'driver.nombre_completo',
            'driver.vat',
            'tractor.placa'
        ],
        'tracto_id' => '=',
        'conductor_id' => '='
    ];

    const sorts = [
        'id' => 'asc',
        'driver_name' => 'accessor',
        'tractor_name' => 'accessor',
    ];


    public function getDriverNameAttribute()
    {
        return $this->driver ? $this->driver->nombre_completo : '';
    }

    public function getTractorNameAttribute()
    {
        return $this->tractor ? $this->tractor->placa : '';
    }

    public function driver()
    {
        return $this->hasOne(Driver::class, 'id', 'conductor_id');
    }

    public function tractor()
    {
        return $this->hasOne(Vehicle::class, 'id', 'tracto_id');
    }
}
