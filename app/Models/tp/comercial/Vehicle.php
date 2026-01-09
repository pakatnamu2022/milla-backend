<?php
namespace App\Models\tp\comercial;


use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;

class Vehicle extends BaseModel
{
     protected $table = 'op_vehiculo';
    public $timestamps = true;

    public function sede()
    {       
        return $this->hasOne(Sede::class, 'id', 'sede_id');
    }

    public function vehicle_type(){
        return $this->hasOne(VehicleType::class, 'id', 'tipo_vehiculo_id');
    }

}