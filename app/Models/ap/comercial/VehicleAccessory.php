<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleAccessory extends Model
{
  use SoftDeletes;

  protected $table = 'vehicle_accessory';

  protected $fillable = [
    'vehicle_purchase_order_id',
    'accessory_id',
    'unit_price',
    'quantity',
  ];

  protected $casts = [
    'unit_price' => 'decimal:2',
    'quantity' => 'integer',
  ];

  /**
   * Relación con la orden de compra de vehículo
   */
  public function vehiclePurchaseOrder(): BelongsTo
  {
    return $this->belongsTo(VehiclePurchaseOrder::class, 'vehicle_purchase_order_id');
  }

  /**
   * Relación con el accesorio
   */
  public function accessory(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'accessory_id');
  }

  /**
   * Calcula el total del accesorio (precio unitario * cantidad)
   */
  public function getTotalAttribute(): float
  {
    return $this->unit_price * $this->quantity;
  }
}
