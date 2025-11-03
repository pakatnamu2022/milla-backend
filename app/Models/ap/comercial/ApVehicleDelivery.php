<?php

namespace App\Models\ap\comercial;

use App\Models\gp\gestionhumana\personal\Worker;
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
    'actual_delivery_date',
    'observations',
    'status_nubefact',
    'status_sunat',
    'status_dynamic',
    'status',
  ];

  protected $casts = [
    'scheduled_delivery_date' => 'date',
    'actual_delivery_date' => 'date',
  ];

  const filters = [
    'search' => [],
    'vehicle_id',
    'scheduled_delivery_date',
    'actual_delivery_date',
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
    'actual_delivery_date',
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
}
