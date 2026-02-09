<?php

namespace App\Models\ap\compras;

use App\Http\Traits\Reportable;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\VehicleAccessory;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
  Modelo para las órdenes de compra
*/

class PurchaseOrder extends BaseModel
{
  use SoftDeletes, Reportable;

  protected $table = 'ap_purchase_order';

  protected $fillable = [
    'number',
    'number_correlative',
    'invoice_series',
    'invoice_number',
    'emission_date',
    'due_date',
    'discount',
    'subtotal',
    'isc',
    'igv',
    'total',
    'payment_term',
    'supplier_id',
    'currency_id',
    'exchange_rate_id',
    'supplier_order_type_id',
    'number_guide',
    'sede_id',
    'warehouse_id',
    'invoice_dynamics',
    'receipt_dynamics',
    'credit_note_dynamics',
    'resent',
    'original_purchase_order_id',
    'migration_status',
    'status',
    'vehicle_movement_id',
    'quotation_id',
    'type_operation_id',
    'migrated_at',
    'payment_terms',
    'notes',
    'created_by',
  ];

  protected $casts = [
    'migrated_at' => 'datetime',
    'emission_date' => 'date',
    'due_date' => 'date',
    'status' => 'boolean',
    'resent' => 'boolean',
    'discount' => 'decimal:2',
    'subtotal' => 'decimal:2',
    'isc' => 'decimal:2',
    'igv' => 'decimal:2',
    'total' => 'decimal:2',
  ];

  const filters = [
    'search' => ['number', 'invoice_series', 'invoice_number', 'number_guide'],
    'supplier_id' => '=',
    'warehouse_id' => '=',
    'migration_status' => '=',
    'status' => '=',
    'currency_id' => '=',
    'sede_id' => '=',
    'vehicle.ap_models_vn_id' => '=',
    'vehicle.ap_vehicle_status_id' => '=',
    'type_operation_id' => '=',
    'quotation_id' => '=',
    'emission_date' => 'between',
    'due_date' => 'between',
  ];

  const sorts = [
    'number',
    'emission_date',
    'total',
  ];

  // SUPPLY TYPE CONSTANTS
  const STOCK = 'STOCK';
  const LIMA = 'LIMA';
  const IMPORTACION = 'IMPORTACION';

  // Relaciones
  public function supplier(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'supplier_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function exchangeRate(): BelongsTo
  {
    return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function items(): HasMany
  {
    return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
  }

  public function receptions(): HasMany
  {
    return $this->hasMany(PurchaseReception::class, 'purchase_order_id');
  }

  public function hasActiveReceptions(): bool
  {
    return $this->receptions()->whereNull('deleted_at')->exists();
  }

  public function vehicleMovement(): BelongsTo
  {
    return $this->belongsTo(VehicleMovement::class, 'vehicle_movement_id');
  }

  public function originalPurchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'original_purchase_order_id');
  }

  public function supplierOrderType(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'supplier_order_type_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  /**
   * Relación con Vehicle a través de VehicleMovement
   * Si la PurchaseOrder tiene un vehicle_movement_id, podemos obtener el vehículo
   */
  public function vehicle(): HasOneThrough
  {
    return $this->hasOneThrough(
      Vehicles::class,
      VehicleMovement::class,
      'id', // Foreign key en vehicle_movement
      'id', // Foreign key en vehicles
      'vehicle_movement_id', // Local key en purchase_order
      'ap_vehicle_id' // Local key en vehicle_movement
    );
  }

  /**
   * Relación con accesorios de vehículo
   * Los accesorios están asociados a la orden de compra
   */
  public function accessories(): HasMany
  {
    return $this->hasMany(VehicleAccessory::class, 'vehicle_purchase_order_id');
  }

  /**
   * Relación a logs de sincronización de credit note
   */
  public function creditNoteSyncLogs(): HasMany
  {
    return $this->hasMany(CreditNoteSyncLog::class);
  }

  /**
   * Relación con la cotización asociada
   */
  public function quotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'quotation_id');
  }

  /**
   * Acceso al asesor a través de: Quotation → WorkOrder → Advisor
   * Retorna el asesor asociado a la orden de trabajo de esta cotización
   */
  public function advisor(): ?Worker
  {
    return $this->quotation?->opportunity->worker;
  }

  /**
   * Mutator para el número de orden de compra con prefijo OC
   * @param $value
   * @return void
   */
  public function setNumberAttribute($value)
  {
    if (str_starts_with($value, 'OC')) {
      $this->attributes['number'] = $value;
    } else {
      $this->attributes['number'] = 'OC' . $value;
    }
  }

  /**
   * Mutator para el número de guía con prefijo NI
   * @param $value
   * @return void
   */
  public function setNumberGuideAttribute($value)
  {
    if (str_starts_with($value, 'NI')) {
      $this->attributes['number_guide'] = $value;
    } else {
      $this->attributes['number_guide'] = 'NI' . $value;
    }
  }
}
