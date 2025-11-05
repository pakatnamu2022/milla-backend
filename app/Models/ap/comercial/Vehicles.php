<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicles extends Model
{
  use SoftDeletes;

  protected $table = 'ap_vehicles';

  protected $fillable = [
    'vin',
    'year',
    'engine_number',
    'ap_models_vn_id',
    'vehicle_color_id',
    'engine_type_id',
    'ap_vehicle_status_id',
    'type_operation_id',
    'status',
    'warehouse_physical_id',
  ];

  protected $casts = [
    'year' => 'integer',
  ];

  public static array $filters = [
    'search' => ['vin', 'engine_number', 'year', 'ap_vehicle_status_id'],
    'ap_models_vn_id' => '=',
    'ap_vehicle_status_id' => 'in',
    'vehicle_color_id' => '=',
    'engine_type_id' => '=',
    'warehouse_physical_id' => '=',
    'year' => '=',
    'has_purchase_request_quote' => 'accessor',
  ];

  public static array $sorts = [
    'vin',
    'year',
    'engine_number',
    'created_at',
  ];

  public function getHasPurchaseRequestQuoteAttribute(): bool
  {
    return $this->purchaseRequestQuote()->exists();
  }

  // Relaciones
  public function purchaseRequestQuote(): HasOne
  {
    return $this->hasOne(PurchaseRequestQuote::class, 'ap_vehicle_id');
  }

  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function color(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function engineType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'engine_type_id');
  }

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function warehousePhysical(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_physical_id');
  }

  public function vehicleMovements(): HasMany
  {
    return $this->hasMany(VehicleMovement::class, 'ap_vehicle_id');
  }

  /**
   * Obtiene todas las órdenes de compra a través de los movimientos del vehículo
   * Un vehículo puede tener múltiples movimientos y cada movimiento puede tener una orden
   */
  public function purchaseOrders(): HasManyThrough
  {
    return $this->hasManyThrough(
      PurchaseOrder::class,       // Modelo final que queremos obtener
      VehicleMovement::class,     // Modelo intermedio
      'ap_vehicle_id',            // Foreign key en vehicle_movement que apunta a vehicles
      'vehicle_movement_id',      // Foreign key en purchase_order que apunta a vehicle_movement
      'id',                       // Local key en vehicles
      'id'                        // Local key en vehicle_movement
    );
  }

  /**
   * Obtiene todas las guías de remisión a través de los movimientos del vehículo
   * Un vehículo puede tener múltiples movimientos y cada movimiento puede tener una guía
   */
  public function shippingGuides(): HasManyThrough
  {
    return $this->hasManyThrough(
      ShippingGuides::class,      // Modelo final que queremos obtener
      VehicleMovement::class,     // Modelo intermedio
      'ap_vehicle_id',            // Foreign key en vehicle_movement que apunta a vehicles
      'vehicle_movement_id',      // Foreign key en shipping_guides que apunta a vehicle_movement
      'id',                       // Local key en vehicles
      'id'                        // Local key en vehicle_movement
    );
  }

  /**
   * Obtiene todos los documentos electrónicos (facturas, boletas, etc.) a través de los movimientos del vehículo
   * Un vehículo puede tener múltiples movimientos y cada movimiento puede tener documentos electrónicos
   */
  public function electronicDocuments(): HasManyThrough
  {
    return $this->hasManyThrough(
      \App\Models\ap\facturacion\ElectronicDocument::class, // Modelo final
      VehicleMovement::class,                                // Modelo intermedio
      'ap_vehicle_id',                                       // Foreign key en vehicle_movement que apunta a vehicles
      'ap_vehicle_movement_id',                              // Foreign key en electronic_documents que apunta a vehicle_movement
      'id',                                                  // Local key en vehicles
      'id'                                                   // Local key en vehicle_movement
    );
  }
}
