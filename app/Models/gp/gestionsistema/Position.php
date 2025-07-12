<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;

class Position extends BaseModel
{
    protected $table = 'rrhh_cargo';
    public $timestamps = false;

//    public function area()
//    {
//        return $this->belongsTo(Area::class, 'area_id');
//    }
//
//    public function tipo_onboarding()
//    {
//        return $this->belongsTo(Tipo_onboarding::class, 'perfil_id');
//    }
//
//    public function perfil()
//    {
//        return $this->belongsTo(PerfilxCargo::class, 'perfil_id');
//    }

//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logAll();
//    }
}
