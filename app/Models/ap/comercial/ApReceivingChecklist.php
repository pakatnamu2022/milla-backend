<?php

namespace App\Models\ap\comercial;

use App\Models\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApReceivingChecklist extends Model
{
  use softDeletes;

  protected $table = 'ap_receiving_checklist';

  protected $fillable = [
    'receiving_id',
    'shipping_guide_id',
    'quantity',
  ];

  const filters = [
    'id',
    'receiving_id',
    'shipping_guide_id',
  ];

  const sorts = [
    'id',
    'receiving_id',
    'shipping_guide_id',
  ];

  public function receiving(): BelongsTo
  {
    return $this->belongsTo(ApDeliveryReceivingChecklist::class, 'receiving_id');
  }

  public function shipping_guide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
  }

  /**
   * Obtiene el vehículo asociado a través de shipping_guide -> vehicleMovement -> vehicle
   */
  public function getVehicleAttribute(): ?Vehicles
  {
    return $this->shipping_guide?->vehicleMovement?->vehicle;
  }
}
