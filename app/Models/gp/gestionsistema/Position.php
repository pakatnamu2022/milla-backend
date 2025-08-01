<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;

class Position extends BaseModel
{
    protected $table = 'rrhh_cargo';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'descripcion',
        'status_deleted',
        'area_id',
        'ntrabajadores',
        'banda_salarial_min',
        'banda_salarial_media',
        'banda_salarial_max',
        'tipo_onboarding_id',
        'write_id',
        'plazo_proceso_seleccion',
        'created_at',
        'updated_at',
        'mof_adjunto',
        'presupuesto',
        'fileadic1',
        'fileadic2',
        'fileadic3',
        'fileadic4',
        'fileadic5',
        'fileadic6',
        'cargo_id',
        'perfil_id',
    ];

    const filters = [
        'search' => ['name', 'descripcion'],
        'name' => 'like',
        'descripcion' => 'like',
        'status_deleted' => '=',
        'area_id' => '=',
        'ntrabajadores' => '=',
        'banda_salarial_min' => '=',
        'banda_salarial_media' => '=',
        'banda_salarial_max' => '=',
        'tipo_onboarding_id' => '=',
    ];

    const sorts = [
        'name' => 'asc',
        'descripcion' => 'asc',
        'status_deleted' => 'asc',
        'area_id' => 'asc',
        'ntrabajadores' => 'asc',
        'banda_salarial_min' => 'asc',
        'banda_salarial_media' => 'asc',
        'banda_salarial_max' => 'asc',
        'tipo_onboarding_id' => 'asc',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function lidership()
    {
        return $this->belongsTo(Position::class, 'cargo_id');
    }
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
