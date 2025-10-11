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
    'ap_vehicle_status_id',
    'ap_vehicle_purchase_order_id',
    'observation',
    'movement_date',
  ];

  const array filters = [
    'id',
    'ap_vehicle_status_id',
    'ap_vehicle_purchase_order_id',
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


}
