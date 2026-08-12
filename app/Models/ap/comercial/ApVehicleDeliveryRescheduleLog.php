<?php

namespace App\Models\ap\comercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApVehicleDeliveryRescheduleLog extends Model
{
  protected $table = 'ap_vehicle_delivery_reschedule_logs';

  protected $fillable = [
    'vehicle_delivery_id',
    'previous_date',
    'new_date',
    'rescheduled_by',
    'is_extraordinary',
    'observations',
  ];

  protected $casts = [
    'previous_date'  => 'datetime',
    'new_date'       => 'datetime',
    'is_extraordinary' => 'boolean',
  ];

  public function vehicleDelivery()
  {
    return $this->belongsTo(ApVehicleDelivery::class, 'vehicle_delivery_id');
  }

  public function rescheduledBy()
  {
    return $this->belongsTo(User::class, 'rescheduled_by');
  }
}
