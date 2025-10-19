<?php

namespace App\Models\ap\comercial;

use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMovement extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_vehicle_movement';

  protected $fillable = [
    'movement_type',
    'ap_vehicle_status_id',
    'ap_vehicle_purchase_order_id',
    'observation',
    'movement_date',
    'origin_address',
    'destination_address',
    'cancellation_reason',
    'cancelled_by',
    'cancelled_at',
    'previous_status_id',
    'new_status_id',
    'created_by',
  ];

  const array filters = [
    'id',
    'movement_type',
    'ap_vehicle_status_id',
    'ap_vehicle_purchase_order_id',
    'previous_status_id',
    'new_status_id',
    'created_by',
  ];

  const array sorts = ['id'];

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  public function vehiclePurchaseOrder(): BelongsTo
  {
    return $this->belongsTo(VehiclePurchaseOrder::class, 'ap_vehicle_purchase_order_id');
  }

  public function getStatusAttribute(): string
  {
    return $this->vehicleStatus->description;
  }

  public function getStatusColorAttribute(): string
  {
    return $this->vehicleStatus->color;
  }


}
