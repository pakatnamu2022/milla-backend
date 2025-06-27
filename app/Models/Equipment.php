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
        'search' => ['equipo', 'marca_modelo', 'serie', 'detalle'],
        'tipo_equipo_id' => '=',
        'sede_id' => '=',
        'status_id' => '=',
    ];

    const sorts = [
        'id' => 'asc',
        'equipo' => 'asc',
        'marca_modelo' => 'asc',
        'serie' => 'asc',
        'status_id' => 'asc',
        'estado_uso' => 'asc',
        'stock_actual' => 'asc',
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

}
