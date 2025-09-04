<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\gp\gestionsistema\Sede;
use App\Models\gp\gestionsistema\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignSedePeriodo extends Model
{
  use softDeletes;

  protected $table = 'ap_assign_sede_periodo';

  protected $fillable = [
    'sede_id',
    'asesor_id',
    'anio',
    'month',
  ];

  const filters = [
    'search' => ['sede_id', 'asesor_id'],
    'sede_id' => '=',
    'asesor_id' => '=',
    'anio' => '=',
    'month' => '=',
  ];

  const sorts = [
    'sede_id',
    'asesor_id',
    'anio',
    'month',
  ];

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function asesor()
  {
    return $this->belongsTo(Person::class, 'asesor_id');
  }
}
