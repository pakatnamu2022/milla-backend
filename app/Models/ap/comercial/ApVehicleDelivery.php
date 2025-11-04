<?php

namespace App\Models\ap\comercial;

use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleDelivery extends Model
{
  use softDeletes;

  protected $table = 'ap_vehicle_delivery';

  protected $fillable = [
    'advisor_id',
    'vehicle_id',
    'scheduled_delivery_date',
    'wash_date',
    'real_delivery_date',
    'real_wash_date',
    'observations',
    'sede_id',
    'status_wash',
    'status_delivery',
    'status_nubefact',
    'status_sunat',
    'status_dynamic',
    'status',
  ];

  protected $casts = [
    'scheduled_delivery_date' => 'date',
    'real_delivery_date' => 'date',
    'wash_date' => 'date',
    'real_wash_date' => 'date',
  ];

  const filters = [
    'search' => [],
    'vehicle_id',
    'scheduled_delivery_date',
    'status_nubefact',
    'status_sunat',
    'status_dynamic',
    'status',
  ];

  const sorts = [
    'id',
    'advisor_id',
    'vehicle_id',
    'scheduled_delivery_date',
    'real_delivery_date',
    'status_nubefact',
    'status_sunat',
    'status_dynamic',
    'status',
  ];

  public function advisor()
  {
    return $this->belongsTo(Worker::class, 'advisor_id');
  }

  public function vehicle()
  {
    return $this->belongsTo(Vehicles::class, 'vehicle_id');
  }

  public function sede()
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }
}
