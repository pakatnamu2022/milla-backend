<?php

namespace App\Models\ap\compras;

use App\Models\ap\maestroGeneral\UnitMeasurement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
  use SoftDeletes;

  protected $table = 'ap_purchase_order_item';

  protected $fillable = [
    'purchase_order_id',
    'unit_measurement_id',
    'description',
    'unit_price',
    'quantity',
    'total',
    'is_vehicle',
  ];

  protected $casts = [
    'unit_price' => 'decimal:2',
    'total' => 'decimal:2',
    'quantity' => 'integer',
    'is_vehicle' => 'boolean',
  ];

  // Relaciones
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
  }

  public function unitMeasurement(): BelongsTo
  {
    return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id');
  }

  /**
   * Accessor para obtener información del vehículo si es_vehicle = true
   * Accede al vehículo a través de la orden de compra -> vehicle_movement -> vehicle
   */
  public function getVehicleInfoAttribute()
  {
    // Si no es un vehículo, retornar null
    if (!$this->is_vehicle) {
      return null;
    }

    // Acceder al vehículo a través de la orden de compra
    return $this->purchaseOrder?->vehicle;
  }
}
