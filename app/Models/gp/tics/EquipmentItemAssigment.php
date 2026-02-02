<?php

namespace App\Models\gp\tics;

use Illuminate\Database\Eloquent\Model;

class EquipmentItemAssigment extends Model
{
  protected $table = 'help_item_asig_equipo';

  protected $fillable = [
    'asig_equipo_id',
    'equipo_id',
    'observacion',
    'status_id',
    'observacion_dev',
  ];

  public function assignment()
  {
    return $this->belongsTo(EquipmentAssigment::class, 'asig_equipo_id');
  }

  public function equipment()
  {
    return $this->belongsTo(Equipment::class, 'equipo_id');
  }
}
