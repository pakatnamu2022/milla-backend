<?php

namespace App\Models\gp\tics;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\tics\PhoneLine;
use App\Models\User;

class EquipmentAssigment extends BaseModel
{
  protected $table = 'help_asig_equipos';

  protected $fillable = [
    'persona_id',
    'phone_line_id',
    'fecha',
    'status_deleted',
    'status_id',
    'write_id',
    'conformidad',
    'fecha_conformidad',
    'unassigned_at',
    'observacion',
    'observacion_unassign',
    'pdf_path',
    'pdf_unassign_path',
  ];

  const filters = [
    'search' => ['worker.nombre_completo', 'accessor:itemsNames'],
    'id' => '=',
    'persona_id' => '=',
    'status_id' => '=',
  ];

  const sorts = [
    'id' => 'desc',
    'fecha' => 'desc',
  ];

  public function worker()
  {
    return $this->belongsTo(Worker::class, 'persona_id');
  }

  public function phoneLine()
  {
    return $this->belongsTo(PhoneLine::class, 'phone_line_id');
  }

  public function getItemsNamesAttribute(): string
  {
    return $this->items->map(function ($item) {
      return $item->equipment->equipo;
    })->implode(', ');
  }

  public function items()
  {
    return $this->hasMany(EquipmentItemAssigment::class, 'asig_equipo_id');
  }

  public function writeUser()
  {
    return $this->belongsTo(User::class, 'write_id');
  }
}
