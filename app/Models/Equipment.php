<?php

namespace App\Models;


class Equipment extends BaseModel
{

    protected $table = "help_equipos";
    protected $primaryKey = 'id';

    protected $fillable = [
        'equipo',
        'tipo_equipo_id',
        'marca_modelo',
        'serie',
        'detalle',
        'ram',
        'almacenamiento',
        'procesador',
        'stock_actual',
        'estado_uso',
        'sede_id',
        'status_id',
        'pertenece_sede',
        'status_deleted'
    ];

    const filters = [
        'id' => '=',
        'equipo' => 'like',
        'tipo_equipo_id' => '=',
        'marca_modelo' => 'like',
        'serie' => 'like',
        'detalle' => 'like',

    ];

    const sorts = [
        'id',
        'sede_id',
        'tipo_equipo_id',
        'serial',
        'activo',
        'created_at',
        'updated_at'
    ];

    public function sede()
    {
        return $this->hasOne(Sede::class, 'id', 'sede_id');
    }

    public function status()
    {
        return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function equipmentType()
    {
        return $this->hasOne(EquipmentType::class, 'id', 'tipo_equipo_id');
    }

//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logAll();
//    }

}
