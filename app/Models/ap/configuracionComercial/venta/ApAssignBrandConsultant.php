<?php

namespace App\Models\ap\configuracionComercial\venta;

use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApAssignBrandConsultant extends Model
{
  use SoftDeletes;

  protected $table = 'ap_assign_brand_consultant';

  protected $fillable = [
    'objetivo_venta',
    'anio',
    'month',
    'status',
    'marca_id',
    'asesor_id',
    'sede_id'
  ];

  const filters = [
    'anio' => '=',
    'month' => '=',
    'status' => '=',
    'marca_id' => 'like',
    'asesor_id' => 'like',
    'sede_id' => 'like',
  ];

  const sorts = [
    'id',
    'anio',
    'month',
    'status',
    'marca_id',
    'asesor_id',
    'sede_id',
  ];

  public function marca()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'marca_id');
  }

  public function asesor()
  {
    return $this->belongsTo(Worker::class, 'asesor_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
