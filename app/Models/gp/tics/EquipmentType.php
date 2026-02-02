<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;

class EquipmentType extends BaseModel
{
//    use LogsActivity;

  protected $table = "help_tipo_equipo";
  protected $primaryKey = 'id';

  protected $fillable = [
    'name',
    'status_deleted'
  ];

  const filters = [
    'id' => '=',
    'search' => ['name'],
    'status_deleted' => '='
  ];

  const sorts = [
    'id',
    'name',
    'status_deleted'
  ];

  public function equipments()
  {
    return $this->hasMany(Equipment::class, 'tipo_equipo_id', 'id');
  }

}
