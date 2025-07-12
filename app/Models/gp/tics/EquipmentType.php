<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;

class EquipmentType extends BaseModel
{
//    use LogsActivity;

    protected $table = "help_tipo_equipo";
    protected $primaryKey = 'id';

    protected $fillable = [
        'equipo',
        'name',
        'status_deleted'
    ];

    const filters = [
        'id' => '=',
        'search' => ['equipo'],
        'status_deleted' => '='
    ];

    const sorts = [
        'id',
        'equipo',
        'status_deleted'
    ];

    public function equipments()
    {
        return $this->hasMany(Equipment::class, 'tipo_equipo_id', 'id');
    }

}
