<?php

namespace App\Models\ap\compras;

use App\Http\Traits\Reportable;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\VehicleAccessory;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
  Modelo para las órdenes de compra
*/

class PurchaseOrder extends BaseModel
{
  use SoftDeletes, Reportable;

  protected $table = 'ap_purchase_order';

  protected $reportColumns = [
    'sede.suc_abrev' => ['label' => 'SEDE', 'width' => 12],
    'number' => ['label' => 'NRO. OC', 'width' => 22],
    'vehicle.vin' => ['label' => 'VIN', 'width' => 22],
    'number_guide' => ['label' => 'NRO. GUIA', 'width' => 22],
    'receipt_dynamics' => ['label' => 'NRO. RECIBO DYN', 'width' => 22],
    'vehicle.engine_number' => ['label' => 'NRO. MOTOR', 'width' => 20],
    'vehicle.year' => ['label' => 'AÑO', 'width' => 10],
    'vehicle.model.family.brand.name' => ['label' => 'MARCA', 'width' => 20],
    'vehicle.model.version' => ['label' => 'MODELO', 'width' => 25],
    'vehicle.color.description' => ['label' => 'COLOR', 'width' => 18],
    'vehicle.vehicleStatus.description' => ['label' => 'ESTADO VEHÍCULO', 'width' => 22],
    'supplier.full_name' => ['label' => 'PROVEEDOR', 'width' => 35],
    'supplierOrderType.description' => ['label' => 'TIPO OC', 'width' => 18],
    'invoice_series' => ['label' => 'SERIE FACTURA', 'width' => 15],
    'invoice_number' => ['label' => 'NRO. FACTURA', 'width' => 15],
    'emission_date' => ['label' => 'FECHA EMISIÓN', 'formatter' => 'date', 'width' => 18],
    'due_date' => ['label' => 'FECHA VENC.', 'formatter' => 'date', 'width' => 18],
    'currency.name' => ['label' => 'MONEDA', 'width' => 12],
    'subtotal' => ['label' => 'SUBTOTAL', 'width' => 15],
    'igv' => ['label' => 'IGV', 'width' => 12],
    'total' => ['label' => 'TOTAL', 'width' => 15],
    'migration_status_translated' => ['label' => 'ESTADO MIGRACIÓN', 'accessor' => 'getMigrationStatusTranslated', 'width' => 20],
    'status' => ['label' => 'ESTADO', 'formatter' => 'boolean', 'true_label' => 'VÁLIDA', 'false_label' => 'ANULADA', 'width' => 15],
    'is_contabilizado' => ['label' => 'CONTABILIZADO', 'accessor' => 'isContabilizado', 'formatter' => 'boolean', 'true_label' => 'SÍ', 'false_label' => 'NO', 'width' => 18],
  ];

  protected $reportRelations = [
    'sede',
    'vehicle.model.family.brand',
    'vehicle.color',
    'vehicle.vehicleStatus',
    'supplier',
    'supplierOrderType',
    'currency',
  ];

  protected $reportColorRules = [
    'migration_status_translated' => [
      'COMPLETADO' => ['bg' => '00AA00', 'text' => 'FFFFFF'], // Verde
      'PENDIENTE' => ['bg' => 'FFA500', 'text' => 'FFFFFF'], // Naranja
      'FALLIDO' => ['bg' => 'FF0000', 'text' => 'FFFFFF'], // Rojo
      'PROCESANDO' => ['bg' => '0066CC', 'text' => 'FFFFFF'], // Azul
    ],
    'status' => [
      true => ['bg' => '00AA00', 'text' => 'FFFFFF'], // Verde para "VÁLIDA"
      false => ['bg' => 'FF0000', 'text' => 'FFFFFF'], // Rojo para "ANULADA"
    ],
    'is_contabilizado' => [
      true => ['bg' => '00AA00', 'text' => 'FFFFFF'], // Verde con texto blanco para "SÍ"
      false => ['bg' => 'FF0000', 'text' => 'FFFFFF'], // Rojo con texto blanco para "NO"
    ],
  ];

  protected $fillable = [
    'number',
    'number_correlative',
    'invoice_series',
    'invoice_number',
    'emission_date',
    'due_date',
    'invoice_date_dyn',
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
    'consignment_shipping_guide_id',
    'quotation_id',
    'type_operation_id',
    'migrated_at',
    'payment_terms',
    'notes',
    'created_by',
    'invoice_sync_attempted_at',
    'invoice_sync_attempts',
  ];

  protected $casts = [
    'migrated_at' => 'datetime',
    'invoice_sync_attempted_at' => 'datetime',
    'emission_date' => 'date',
    'due_date' => 'date',
    'invoice_date_dyn' => 'date',
    'status' => 'boolean',
    'resent' => 'boolean',
    'discount' => 'decimal:2',
    'subtotal' => 'decimal:2',
    'isc' => 'decimal:2',
    'igv' => 'decimal:2',
    'total' => 'decimal:2',
  ];

  const filters = [
    'search' => ['number', 'invoice_series', 'invoice_number', 'number_guide', 'vehicle.vin'],
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
  const CENTRAL = 'CENTRAL';
  const IMPORTACION = 'IMPORTACION';

  // Relaciones
  public function supplier(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'supplier_id');
  }

  public function migrationLogs(): HasMany
  {
    return $this->hasMany(VehiclePurchaseOrderMigrationLog::class, 'vehicle_purchase_order_id');
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

  public function reception(): HasOne // A una recepción le corresponde una factura (OC)
  {
    return $this->hasOne(PurchaseReception::class, 'purchase_order_id');
  }

  public function hasActiveReceptions(): bool
  {
    if ($this->relationLoaded('reception')) {
      return $this->reception !== null && $this->reception->deleted_at === null;
    }
    return $this->reception()->whereNull('deleted_at')->exists();
  }

  public function vehicleMovement(): BelongsTo
  {
    return $this->belongsTo(VehicleMovement::class, 'vehicle_movement_id');
  }

  public function originalPurchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'original_purchase_order_id');
  }

  public function consignmentGuide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'consignment_shipping_guide_id');
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
    return $this->belongsTo(PurchaseRequestQuote::class, 'quotation_id');
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

  public function setMigrationStatusAttribute($value)
  {
    $this->attributes['migration_status'] = $value !== null ? strtoupper($value) : null;
  }

  /**
   * Filtra las columnas del reporte según el contexto
   * Si el tipo de operación es POSTVENTA, oculta las columnas relacionadas con vehículos
   *
   * @param array $columns
   * @param array $context
   * @return array
   */
  public function filterReportColumns(array $columns, array $context = []): array
  {
    // Si el tipo de operación es POSTVENTA, excluir columnas de vehículos
    if (isset($context['type_operation_id']) && $context['type_operation_id'] == ApMasters::TIPO_OPERACION_POSTVENTA) {
      $vehicleColumns = [
        'vehicle.vin',
        'vehicle.engine_number',
        'vehicle.year',
        'vehicle.model.family.brand.name',
        'vehicle.model.name',
        'vehicle.color.description',
        'vehicle.vehicleStatus.description',
      ];

      foreach ($vehicleColumns as $column) {
        unset($columns[$column]);
      }
    }

    return $columns;
  }

  /**
   * Obtiene el siguiente número correlativo para una orden de compra
   * Fuente de verdad centralizada para la asignación de correlativos
   *
   * Lógica especial:
   * - Si es TIPO_OPERACION_POSTVENTA: omite el filtro where('status', true)
   * - Si NO es TIPO_OPERACION_POSTVENTA: incluye el filtro where('status', true)
   *
   * @param int $sedeId
   * @param int $typeOperationId
   * @param int $correlativeStart Número inicial si no hay correlativos previos (default: 1)
   * @return int
   */
  public static function getNextCorrelative(int $sedeId, int $typeOperationId, int $correlativeStart = 1): int
  {
    $query = self::where('sede_id', $sedeId)
      ->where('type_operation_id', $typeOperationId)
      ->whereNull('deleted_at');

    // Condición especial: solo aplicar where('status', true) si NO es TIPO_OPERACION_POSTVENTA
    if ($typeOperationId !== ApMasters::TIPO_OPERACION_POSTVENTA) {
      $query->where('status', true);
    }

    $maxCorrelative = $query->max('number_correlative');

    return $maxCorrelative ? $maxCorrelative + 1 : $correlativeStart;
  }

  /**
   * Accessor para determinar si la orden de compra está contabilizada
   * Una orden está contabilizada cuando ambos campos invoice_dynamics y receipt_dynamics tienen valor
   *
   * @return bool
   */
  public function getIsContabilizadoAttribute(): bool
  {
    return !empty($this->invoice_dynamics) && !empty($this->receipt_dynamics);
  }

  /**
   * Método auxiliar para acceder al atributo is_contabilizado
   * Usado por el sistema de reportes con accessor
   *
   * @return bool
   */
  public function isContabilizado(): bool
  {
    return $this->is_contabilizado;
  }

  /**
   * Traduce el estado de migración al español
   * Usado por el sistema de reportes con accessor
   *
   * @return string
   */
  public function getMigrationStatusTranslated(): string
  {
    $translations = [
      'PENDING' => 'PENDIENTE',
      'COMPLETED' => 'COMPLETADO',
      'FAILED' => 'FALLIDO',
      'PROCESSING' => 'PROCESANDO',
    ];

    $status = strtoupper($this->migration_status ?? '');
    return $translations[$status] ?? $status;
  }
}
