<?php

namespace App\Models\ap\comercial;

use App\Http\Traits\Reportable;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehiclePurchaseOrder extends Model
{
  use softDeletes, Reportable;

  protected $table = 'ap_vehicle_purchase_order';

  protected $fillable = [
//    vehicle
    'vin',
    'year',
    'engine_number',
    'status', // Boolean: true=activa, false=anulada
    'ap_models_vn_id',
    'vehicle_color_id',
    'supplier_order_type_id',
    'engine_type_id',
    'ap_vehicle_status_id',
    'sede_id',
    'original_purchase_order_id',

//    Invoice
    'invoice_series',
    'invoice_number',
    'emission_date',
    'unit_price',
    'discount',
    'subtotal',
    'igv',
    'total',
    'supplier_id',
    'currency_id',
    'exchange_rate_id',

//    Guide
    'number',
    'number_guide',
    'warehouse_id',
    'warehouse_physical_id',

//    Migration status
    'invoice_dynamics',
    'receipt_dynamics',
    'credit_note_dynamics',
    'migration_status',
    'migrated_at',
    'resent',

  ];

  protected $casts = [
    'migrated_at' => 'datetime',
    'status' => 'boolean',
    'resent' => 'boolean',
  ];

  const filters = [
    'search' => ['vin', 'order_number', 'engine_number', 'invoice_series', 'invoice_number', 'number', 'number_guide'],
    'sede_id' => '=',
    'warehouse_id' => '=',
    'supplier_id' => '=',
    'year' => '=',
    'ap_models_vn_id' => '=',
    'vehicle_color_id' => '=',
    'ap_vehicle_status_id' => '=',
    'migration_status' => '=',
  ];

  const sorts = [
    'vin',
  ];

  public function setNumberAttribute($value)
  {
    // Si el valor ya empieza con 'OC', no agregar el prefijo nuevamente
    if (str_starts_with($value, 'OC')) {
      $this->attributes['number'] = $value;
    } else {
      $this->attributes['number'] = 'OC' . $value;
    }
  }

  public function setNumberGuideAttribute($value)
  {
    // Si el valor ya empieza con 'NI', no agregar el prefijo nuevamente
    if (str_starts_with($value, 'NI')) {
      $this->attributes['number_guide'] = $value;
    } else {
      $this->attributes['number_guide'] = 'NI' . $value;
    }
  }

  public function getModelCodeAttribute(): string
  {
    return $this->model->code;
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'supplier_id');
  }

  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'ap_models_vn_id');
  }

  public function color(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'vehicle_color_id');
  }

  public function supplierType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'supplier_order_type_id');
  }

  public function engineType(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'engine_type_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function vehicleStatus(): BelongsTo
  {
    return $this->belongsTo(ApVehicleStatus::class, 'ap_vehicle_status_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function warehousePhysical(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_physical_id');
  }

  public function movements()
  {
    return $this->hasMany(VehicleMovement::class, 'ap_vehicle_purchase_order_id');
  }

  public function exchangeRate(): BelongsTo
  {
    return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
  }

  public function migrationLogs(): HasMany
  {
    return $this->hasMany(VehiclePurchaseOrderMigrationLog::class, 'vehicle_purchase_order_id');
  }

  /**
   * Scope para filtrar por estado de migración
   */
  public function scopeMigrationStatus($query, string $status)
  {
    return $query->where('migration_status', $status);
  }

  /**
   * Scope para obtener órdenes no migradas
   */
  public function scopeNotMigrated($query)
  {
    return $query->whereIn('migration_status', ['pending', 'in_progress', 'failed']);
  }

  /**
   * Scope para obtener órdenes migradas
   */
  public function scopeMigrated($query)
  {
    return $query->where('migration_status', 'completed');
  }

  public function getStatusAttribute($value)
  {
    return $value ? 'Activa' : 'Anulada';
  }

  public function getStatusMigrationAttribute($value)
  {
    return match ($value) {
      'pending' => 'Pendiente',
      'in_progress' => 'En Proceso',
      'completed' => 'Completada',
      'failed' => 'Fallida',
      'updated_with_nc' => 'Actualizada con NC',
      default => 'Desconocido',
    };
  }

  // ← CONFIGURACIÓN DEL REPORTE CON FORMATO SOLICITADO
  protected $reportColumns = [
    'sede.abreviatura' => [
      'label' => 'Sede',
      'formatter' => null,
      'width' => 20,
    ],
    'vin' => [
      'label' => 'VIN',
      'formatter' => null,
      'width' => 15,
//      'accessor' => 'vin'
    ],
    'year' => [
      'label' => 'Año',
      'formatter' => null,
      'width' => 20,
    ],
    'engine_number' => [
      'label' => 'Número Motor',
      'formatter' => null,
      'width' => 20,
    ],
    'engineType.description' => [
      'label' => 'Tipo Orden',
      'formatter' => null,
      'width' => 20,
    ],
    'supplier.full_name' => [
      'label' => 'Proveedor',
      'formatter' => null,
      'width' => 20,
    ],
    'supplier.num_doc' => [
      'label' => 'RUC',
      'formatter' => null,
      'width' => 20,
    ],
    'currency.name' => [
      'label' => 'Moneda',
      'formatter' => null,
      'width' => 20,
    ],
    'model.version' => [
      'label' => 'Modelo',
      'formatter' => null,
      'width' => 20,
    ],
    'model_code' => [
      'label' => 'Código Modelo',
      'formatter' => null,
      'width' => 20,
    ],
    'color.description' => [
      'label' => 'Color',
      'formatter' => null,
      'width' => 20,
    ],
    'supplierType.description' => [
      'label' => 'Tipo Orden',
      'formatter' => null,
      'width' => 20,
    ],
    'vehicleStatus.description' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 20,
    ],
    'warehouse.description' => [
      'label' => 'Almacén',
      'formatter' => null,
      'width' => 20,
    ],
    'warehousePhysical.description' => [
      'label' => 'Almacén Físico',
      'formatter' => null,
      'width' => 20,
    ],
    'exchangeRate.rate' => [
      'label' => 'Tipo Cambio',
      'formatter' => null,
      'width' => 20,
    ],
    'number' => [
      'label' => 'N° Orden',
      'formatter' => null,
      'width' => 20,
    ],
    'number_guide' => [
      'label' => 'N° Ingreso',
      'formatter' => null,
      'width' => 20,
    ],
    'invoice_dynamics' => [
      'label' => 'N° Factura Dynamics',
      'formatter' => null,
      'width' => 20,
    ],
    'receipt_dynamics' => [
      'label' => 'N° Recibo Dynamics',
      'formatter' => null,
      'width' => 20,
    ],
    'credit_note_dynamics' => [
      'label' => 'N° Nota Crédito Dynamics',
      'formatter' => null,
      'width' => 20,
    ],
    'status' => [
      'label' => 'Estado',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getStatusAttribute'
    ],
    'migration_status' => [
      'label' => 'Estado Migración',
      'formatter' => null,
      'width' => 20,
      'accessor' => 'getStatusMigrationAttribute'
    ],
  ];

  protected $reportRelations = [
    'supplier',
    'currency',
    'model',
    'color',
    'supplierType',
    'engineType',
    'sede',
    'vehicleStatus',
    'warehouse',
    'warehousePhysical',
    'exchangeRate',
    'exchangeRate'

  ];
}
