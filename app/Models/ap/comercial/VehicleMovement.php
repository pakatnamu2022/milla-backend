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
    'ap_vehicle_id',
    'ap_vehicle_status_id',
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

  protected $casts = [
    'movement_date' => 'datetime',
    'cancelled_at' => 'datetime',
  ];

  const array filters = [
    'id',
    'ap_vehicle_id',
    'movement_type',
    'ap_vehicle_status_id',
    'previous_status_id',
    'new_status_id',
    'created_by',
  ];

  const array sorts = ['id', 'ap_vehicle_id'];

  const ORDERED = 'PEDIDO';
  const IN_TRANSIT = 'EN TRAVESIA';
  const IN_TRANSIT_RETURNED = 'EN TRAVESIA DEVUELTO';

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'ap_vehicle_id');
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
