<?php

namespace App\Models\ap\compras;

use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
  use SoftDeletes;

  protected $table = 'ap_purchase_order_item';

  protected $fillable = [
    'purchase_order_id',
    'product_id',
    'unit_measurement_id',
    'description',
    'unit_price',
    'quantity',
    'quantity_received',
    'quantity_pending',
    'total',
    'is_vehicle',
  ];

  protected $casts = [
    'unit_price' => 'decimal:2',
    'total' => 'decimal:2',
    'quantity' => 'decimal:2',
    'quantity_received' => 'decimal:2',
    'quantity_pending' => 'decimal:2',
    'is_vehicle' => 'boolean',
  ];

  // Relaciones
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function unitMeasurement(): BelongsTo
  {
    return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id');
  }

  public function receptionDetails(): HasMany
  {
    return $this->hasMany(PurchaseReceptionDetail::class, 'purchase_order_item_id');
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

  /**
   * Check if item is fully received
   */
  public function getIsFullyReceivedAttribute(): bool
  {
    return $this->quantity_received >= $this->quantity;
  }

  /**
   * Check if item has pending quantity
   */
  public function getHasPendingQuantityAttribute(): bool
  {
    return $this->quantity_pending > 0;
  }

  /**
   * Scopes
   */
  public function scopeProducts($query)
  {
    return $query->where('is_vehicle', false)->whereNotNull('product_id');
  }

  public function scopeVehicles($query)
  {
    return $query->where('is_vehicle', true);
  }

  public function scopePendingReception($query)
  {
    return $query->where('quantity_pending', '>', 0);
  }

  public function scopeFullyReceived($query)
  {
    return $query->whereColumn('quantity_received', '>=', 'quantity');
  }
}
