<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;

class EquipmentAssigment extends BaseModel
{
  protected $table = 'help_asig_equipos';

  protected $fillable = [
    'persona_id',
    'fecha',
    'status_deleted',
    'status_id',
    'write_id',
    'conformidad',
    'fecha_conformidad',
  ];

  const filters = [
    'id' => '=',
    'persona_id' => '=',
    'status_id' => '=',
    'search' => ['persona_id'],
  ];

  const sorts = [
    'id' => 'desc',
    'fecha' => 'desc',
  ];

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'persona_id');
  }

  public function items()
  {
    return $this->hasMany(EquipmentItemAssigment::class, 'asig_equipo_id');
  }
}
