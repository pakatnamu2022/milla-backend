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
   * Relación polimórfica para obtener información específica del vehículo si es_vehicle = true
   * Esta relación se implementará cuando tengamos la tabla de vehículos asociada
   */
  public function vehicleInfo()
  {
    // TODO: Implementar relación con tabla de información de vehículos
    // Por ahora retornamos null
    return null;
  }
}
