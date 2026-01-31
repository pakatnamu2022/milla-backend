<?php

namespace App\Models\gp\tics;

use Illuminate\Database\Eloquent\Model;

class EquipmentAssigment extends Model
{
  protected $table = 'help_asig_equipos';

  protected $fillable = [
    'id',
    'persona_id',
    'fecha',
    'status_deleted',
    'status_id',
    'write_id',
    'conformidad',
    'fecha_conformidad',
    'created_at',
    'updated_at',
  ];
}
