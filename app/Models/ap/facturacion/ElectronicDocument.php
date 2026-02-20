<?php

namespace App\Models\ap\facturacion;

use App\Http\Services\BaseService;
use App\Http\Traits\Reportable;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\venta\ApBank;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectronicDocument extends BaseModel
{
  use SoftDeletes, Reportable;

  protected $table = 'ap_billing_electronic_documents';

  protected $fillable = [
    'sunat_concept_document_type_id',
    'serie',
    'series_id',
    'numero',
    'full_number',
    'is_advance_payment',
    'sunat_concept_transaction_type_id',
    'area_id',
    'origin_entity_type',
    'origin_entity_id',
    'ap_vehicle_movement_id',
    'client_id',
    'purchase_request_quote_id',
    'order_quotation_id',
    'work_order_id',
    'credit_note_id',
    'debit_note_id',
    'sunat_concept_identity_document_type_id',
    'cliente_numero_de_documento',
    'cliente_denominacion',
    'cliente_direccion',
    'cliente_email',
    'cliente_email_1',
    'cliente_email_2',
    'fecha_de_emision',
    'fecha_de_vencimiento',
    'sunat_concept_currency_id',
    'tipo_de_cambio',
    'exchange_rate_id',
    'porcentaje_de_igv',
    'descuento_global',
    'total_descuento',
    'total_anticipo',
    'total_gravada',
    'total_inafecta',
    'total_exonerada',
    'total_igv',
    'total_gratuita',
    'total_otros_cargos',
    'total_isc',
    'total',
    'percepcion_tipo',
    'percepcion_base_imponible',
    'total_percepcion',
    'total_incluido_percepcion',
    'retencion_tipo',
    'retencion_base_imponible',
    'total_retencion',
    'detraccion',
    'sunat_concept_detraction_type_id',
    'detraccion_total',
    'detraccion_porcentaje',
    'medio_de_pago_detraccion',
    'documento_que_se_modifica_tipo',
    'documento_que_se_modifica_serie',
    'documento_que_se_modifica_numero',
    'original_document_id',
    'sunat_concept_credit_note_type_id',
    'sunat_concept_debit_note_type_id',
    'observaciones',
    'condiciones_de_pago',
    'medio_de_pago',
    'bank_id',
    'operation_number',
    'financing_type',
    'placa_vehiculo',
    'orden_compra_servicio',
    'codigo_unico',
    'enviar_automaticamente_a_la_sunat',
    'enviar_automaticamente_al_cliente',
    'generado_por_contingencia',
    'enlace',
    'enlace_del_pdf',
    'enlace_del_xml',
    'enlace_del_cdr',
    'aceptada_por_sunat',
    'sunat_description',
    'sunat_note',
    'sunat_responsecode',
    'sunat_soap_error',
    'anulado',
    'cadena_para_codigo_qr',
    'codigo_hash',
    'status',
    'migration_status',
    'error_message',
    'sent_at',
    'migrated_at',
    'accepted_at',
    'cancelled_at',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'fecha_de_emision' => 'date',
    'fecha_de_vencimiento' => 'date',
    'tipo_de_cambio' => 'decimal:3',
    'porcentaje_de_igv' => 'decimal:2',
    'descuento_global' => 'decimal:2',
    'total_descuento' => 'decimal:2',
    'total_anticipo' => 'decimal:2',
    'total_gravada' => 'decimal:2',
    'total_inafecta' => 'decimal:2',
    'total_exonerada' => 'decimal:2',
    'total_igv' => 'decimal:2',
    'total_gratuita' => 'decimal:2',
    'total_otros_cargos' => 'decimal:2',
    'total_isc' => 'decimal:2',
    'total' => 'decimal:2',
    'percepcion_base_imponible' => 'decimal:2',
    'total_percepcion' => 'decimal:2',
    'total_incluido_percepcion' => 'decimal:2',
    'retencion_base_imponible' => 'decimal:2',
    'total_retencion' => 'decimal:2',
    'detraccion' => 'boolean',
    'detraccion_total' => 'decimal:10',
    'detraccion_porcentaje' => 'decimal:5',
    'enviar_automaticamente_a_la_sunat' => 'boolean',
    'enviar_automaticamente_al_cliente' => 'boolean',
    'generado_por_contingencia' => 'boolean',
    'aceptada_por_sunat' => 'boolean',
    'sent_at' => 'datetime',
    'accepted_at' => 'datetime',
    'migrated_at' => 'datetime',
    'cancelled_at' => 'datetime',
  ];

  const array filters = [
    'search' => ['full_number', 'cliente_denominacion', 'cliente_numero_de_documento'],
    'original_document_id' => '=',
    'is_advance_payment' => '=',
    'sunat_concept_document_type_id' => '=',
    'serie' => '=',
    'numero' => '=',
    'area_id' => 'in_or_equal',
    'origin_entity_type' => '=',
    'origin_entity_id' => '=',
    'ap_vehicle_movement_id' => '=',
    'purchase_request_quote_id' => '=',
    'order_quotation_id' => '=',
    'work_order_id' => '=',
    'cliente_numero_de_documento' => '=',
    'sunat_concept_currency_id' => '=',
    'status' => '=',
    'aceptada_por_sunat' => '=',
    'anulado' => '=',
    'fecha_de_emision' => '=',
    'created_by' => '=',
    'seriesModel.sede_id' => '=',
  ];

  const array sorts = ['id', 'fecha_de_emision', 'numero', 'total'];

  // Estados
  const STATUS_DRAFT = 'draft';
  const STATUS_SENT = 'sent';
  const STATUS_ACCEPTED = 'accepted';
  const STATUS_REJECTED = 'rejected';
  const STATUS_CANCELLED = 'cancelled';

  // Tipos de documento (IDs de sunat_concepts)
  const TYPE_FACTURA = SunatConcepts::ID_FACTURA_ELECTRONICA;              // 29
  const TYPE_BOLETA = SunatConcepts::ID_BOLETA_VENTA_ELECTRONICA;          // 30
  const TYPE_NOTA_CREDITO = SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA;    // 31
  const TYPE_NOTA_DEBITO = SunatConcepts::ID_NOTA_DEBITO_ELECTRONICA;      // 32

  /**
   * Booted
   */
  protected static function booted()
  {
    static::saving(function ($model) {
      $service = new BaseService();
      $numero = $service->completeNumber($model->numero);
      $model->full_number = "{$model->serie}-{$numero}";
    });

    static::saved(function ($model) {
      if ($model->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        $quote = $model->purchaseRequestQuote;
        $opportunity = Opportunity::find($quote->opportunity_id);
        $opportunity->update([
          'opportunity_status_id' => ApMasters::where('code', Opportunity::SOLD)
            ->whereNull('deleted_at')
            ->first()
            ->id,
        ]);
      }
    });
  }

  /**
   * Relaciones
   * @throws Exception
   */
  public function warehouse()
  {
    $series = AssignSalesSeries::find($this->series_id);
    $sedeId = $series->sede_id;
    if ($this->area_id == ApMasters::AREA_COMERCIAL) {
      if ($this->ap_vehicle_movement_id) {
        $ap_vehicle_movement = $this->vehicleMovement;
        $model = $ap_vehicle_movement->vehicle->model;
        $warehouse = Warehouse::where('article_class_id', $model->class_id)->where('sede_id', $sedeId)
          ->active()->received()->first();
        return $warehouse->dyn_code;
      } else if (!$this->purchaseRequestQuote) {
        $warehouse = Warehouse::where('sede_id', $sedeId)
          ->commercial()->received()->active()->first();
        return $warehouse->dyn_code;
      } else if (!$this->purchaseRequestQuote->has_vehicle) {
        $warehouse = Warehouse::where('sede_id', $sedeId)
          ->commercial()->received()->active()->first();
        return $warehouse->dyn_code;
      } else {
        throw new Exception("No se pudo determinar el almacén para el documento electrónico ID: {$this->id}");
      }
    } else {
      $warehouse = Warehouse::where('sede_id', $sedeId)
        ->postSale()->active()->received()->first();
      return $warehouse->dyn_code;
    }
  }

  public function inventoryMovement()
  {
    return $this->belongsTo(VehicleMovement::class, 'ap_vehicle_movement_id');
  }

  public function getSedeIdAttribute()
  {
    $series = AssignSalesSeries::find($this->series_id);
    return $series ? $series->sede_id : null;
  }

  public function bank()
  {
    return $this->belongsTo(ApBank::class, 'bank_id');
  }

  public function seriesModel(): BelongsTo
  {
    return $this->belongsTo(AssignSalesSeries::class, 'series_id');
  }

  public function creditNote(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocument::class, 'credit_note_id');
  }

  public function originalDocument(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocument::class, 'original_document_id');
  }

  public function debitNote(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocument::class, 'debit_note_id');
  }

  public function documentType(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_document_type_id');
  }

  public function transactionType(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_transaction_type_id');
  }

  public function identityDocumentType(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_identity_document_type_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_currency_id');
  }

  public function detractionType(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_detraction_type_id');
  }

  public function creditNoteType(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_credit_note_type_id');
  }

  public function debitNoteType(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'sunat_concept_debit_note_type_id');
  }

  public function vehicleMovement(): BelongsTo
  {
    return $this->belongsTo(VehicleMovement::class, 'ap_vehicle_movement_id');
  }

  public function vehicle(): HasOneThrough
  {
    return $this->hasOneThrough(
      Vehicles::class,           // Modelo destino final
      VehicleMovement::class,    // Modelo intermedio
      'id',                      // Clave en VehicleMovement que se une con Trip (foreign key local)
      'id',                      // Clave en Vehicles a la que se une VehicleMovement
      'ap_vehicle_movement_id',  // Clave en Trip que apunta a VehicleMovement
      'ap_vehicle_id'            // Clave en VehicleMovement que apunta a Vehicles
    );
  }

  public function client(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'client_id');
  }

  public function items(): HasMany
  {
    return $this->hasMany(ElectronicDocumentItem::class, 'ap_billing_electronic_document_id');
  }

  public function guides(): HasMany
  {
    return $this->hasMany(ElectronicDocumentGuide::class, 'ap_billing_electronic_document_id');
  }

  public function installments(): HasMany
  {
    return $this->hasMany(ElectronicDocumentInstallment::class, 'ap_billing_electronic_document_id');
  }

  public function logs(): HasMany
  {
    return $this->hasMany(NubefactLog::class, 'ap_billing_electronic_document_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function updater(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  /**
   * Scopes
   */
  public function scopeFacturas($query)
  {
    return $query->where('sunat_concept_document_type_id', self::TYPE_FACTURA);
  }

  public function scopeBoletas($query)
  {
    return $query->where('sunat_concept_document_type_id', self::TYPE_BOLETA);
  }

  public function scopeNotasCredito($query)
  {
    return $query->where('sunat_concept_document_type_id', self::TYPE_NOTA_CREDITO);
  }

  public function scopeNotasDebito($query)
  {
    return $query->where('sunat_concept_document_type_id', self::TYPE_NOTA_DEBITO);
  }

  public function scopeComercial($query)
  {
    return $query->where('area_id', ApMasters::AREA_COMERCIAL);
  }

  public function scopePostventa($query)
  {
    return $query->whereIn('area_id', ApMasters::AREAS_POSVENTA);
  }

  public function scopeTaller($query)
  {
    return $query->where('area_id', ApMasters::AREA_TALLER);
  }

  public function scopeMeson($query)
  {
    return $query->where('area_id', ApMasters::AREA_MESON);
  }

  public function scopeAccepted($query)
  {
    return $query->where('status', self::STATUS_ACCEPTED)
      ->where('aceptada_por_sunat', true);
  }

  public function scopePending($query)
  {
    return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT]);
  }

  public function scopeAnticipos($query)
  {
    return $query->where('is_advance_payment', true);
  }

  public function scopeByOriginEntity($query, int $areaId, string $entityType, int $entityId)
  {
    return $query->where('area_id', $areaId)
      ->where('origin_entity_type', $entityType)
      ->where('origin_entity_id', $entityId);
  }

  public function scopeAcceptedBySunat($query)
  {
    return $query->where('aceptada_por_sunat', true);
  }

  public function scopeNotCancelled($query)
  {
    return $query->where('anulado', false);
  }

  /**
   * Accessors
   */
  public function getDocumentNumberAttribute(): string
  {
    return "{$this->serie}-{$this->numero}";
  }

  public function getIsFacturaAttribute(): bool
  {
    return $this->sunat_concept_document_type_id === self::TYPE_FACTURA;
  }

  public function getIsBoletaAttribute(): bool
  {
    return $this->sunat_concept_document_type_id === self::TYPE_BOLETA;
  }

  public function getIsNotaCreditoAttribute(): bool
  {
    return $this->sunat_concept_document_type_id === self::TYPE_NOTA_CREDITO;
  }

  public function getIsNotaDebitoAttribute(): bool
  {
    return $this->sunat_concept_document_type_id === self::TYPE_NOTA_DEBITO;
  }

  public function getIsAcceptedAttribute(): bool
  {
    return $this->status === self::STATUS_ACCEPTED && $this->aceptada_por_sunat === true;
  }

  public function getIsPendingAttribute(): bool
  {
    return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT]);
  }

  public function getIsRejectedAttribute(): bool
  {
    return $this->status === self::STATUS_REJECTED;
  }

  public function getIsCancelledAttribute(): bool
  {
    return $this->status === self::STATUS_CANCELLED || $this->anulado === true;
  }

  public function getSaleDateAttribute(): string
  {
    return $this->fecha_de_emision->format('d/m/Y');
  }

  public function getClientPhoneAttribute(): ?string
  {
    $client = $this->client;
    return $client ? $client->phone : null;
  }

  /**
   * Métodos de negocio
   */
  public function markAsSent(): void
  {
    $this->update([
      'status' => self::STATUS_SENT,
      'sent_at' => now(),
    ]);
  }

  public function markAsAccepted(array $sunatResponse): void
  {
    $this->update([
      'status' => self::STATUS_ACCEPTED,
      'aceptada_por_sunat' => true,
      'accepted_at' => now(),
      'sunat_responsecode' => $sunatResponse['sunat_responsecode'] ?? null,
      'sunat_description' => $sunatResponse['sunat_description'] ?? null,
      'sunat_note' => $sunatResponse['sunat_note'] ?? null,
      'enlace' => $sunatResponse['enlace'] ?? null,
      'enlace_del_pdf' => $sunatResponse['enlace_del_pdf'] ?? null,
      'enlace_del_xml' => $sunatResponse['enlace_del_xml'] ?? null,
      'enlace_del_cdr' => $sunatResponse['enlace_del_cdr'] ?? null,
      'cadena_para_codigo_qr' => $sunatResponse['cadena_para_codigo_qr'] ?? null,
      'codigo_hash' => $sunatResponse['codigo_hash'] ?? null,
    ]);
  }

  public function markAsRejected(string $errorMessage, array $sunatResponse = []): void
  {
    $this->update([
      'status' => self::STATUS_REJECTED,
      'aceptada_por_sunat' => false,
      'error_message' => $errorMessage,
      'sunat_responsecode' => $sunatResponse['sunat_responsecode'] ?? null,
      'sunat_description' => $sunatResponse['sunat_description'] ?? null,
      'sunat_note' => $sunatResponse['sunat_note'] ?? null,
      'sunat_soap_error' => $sunatResponse['sunat_soap_error'] ?? null,
    ]);
  }

  public function markAsCancelled(): void
  {
    $this->update([
      'anulado' => true,
      'cancelled_at' => now(),
    ]);
  }


  /**
   * Marca el paso como en progreso
   */
  public function markAsInProgress(): void
  {
    $this->update([
      'migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
    ]);
  }

  /**
   * Marca el paso como en completado
   * @return void
   */
  public function markAsCompleted(): void
  {
    $this->update([
      'migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED,
    ]);
  }

  public function markAsLocalCancelled(string $reason = null): void
  {
    $this->update([
      'status' => self::STATUS_CANCELLED,
      'observaciones' => $reason ? $this->observaciones . "\n\nAnulado: " . $reason : $this->observaciones,
    ]);
  }

  /**
   * Calcular el siguiente número correlativo para una serie
   */
  public static function getNextNumber(int $documentTypeId, string $serie): int
  {
    $lastDocument = self::where('sunat_concept_document_type_id', $documentTypeId)
      ->where('serie', $serie)
      ->orderBy('numero', 'desc')
      ->first();

    return $lastDocument ? $lastDocument->numero + 1 : 1;
  }

  /**
   * Validar que la serie sea correcta según el tipo de documento
   */
  public static function validateSerie(int $documentTypeId, string $serie): bool
  {
    $prefix = substr($serie, 0, 1);

    return match ($documentTypeId) {
      self::TYPE_FACTURA => $prefix === 'F',
      self::TYPE_BOLETA => $prefix === 'B',
      self::TYPE_NOTA_CREDITO, self::TYPE_NOTA_DEBITO => in_array($prefix, ['F', 'B']),
      default => false,
    };
  }

  /**
   * Métodos helper para anticipos
   */
  public function isAnticipo(): bool
  {
    return $this->is_advance_payment;
  }

  public function isRegularized(): bool
  {
    // Un anticipo está regularizado si existe un documento que lo referencia en sus items
    return ElectronicDocument::whereHas('items', function ($query) {
      $query->where('anticipo_regularizacion', true)
        ->where('anticipo_documento_serie', $this->serie)
        ->where('anticipo_documento_numero', $this->numero);
    })->exists();
  }

  public function purchaseRequestQuote(): HasOne
  {
    return $this->hasOne(PurchaseRequestQuote::class, 'id', 'purchase_request_quote_id');
  }

  public function orderQuotation(): HasOne
  {
    return $this->hasOne(ApOrderQuotations::class, 'id', 'order_quotation_id');
  }

  public function workOrder(): HasOne
  {
    return $this->hasOne(ApWorkOrder::class, 'id', 'work_order_id');
  }

  public function area(): HasOne
  {
    return $this->hasOne(ApMasters::class, 'id', 'area_id');
  }

  /**
   * Columnas para reportes con Reportable trait
   */
  protected $reportColumns = [
    'id' => [
      'label' => 'ID',
      'formatter' => null,
    ],
    'full_number' => [
      'label' => 'NÚMERO DOCUMENTO',
      'formatter' => null,
    ],
    'documentType.description' => [
      'label' => 'TIPO DOCUMENTO',
      'formatter' => null,
    ],
    'fecha_de_emision' => [
      'label' => 'FECHA EMISIÓN',
      'formatter' => 'date',
    ],
    'fecha_de_vencimiento' => [
      'label' => 'FECHA VENCIMIENTO',
      'formatter' => 'date',
    ],
    'cliente_numero_de_documento' => [
      'label' => 'NRO DOCUMENTO CLIENTE',
      'formatter' => null,
    ],
    'identityDocumentType.description' => [
      'label' => 'TIPO DOC. CLIENTE',
      'formatter' => null,
    ],
    'cliente_denominacion' => [
      'label' => 'CLIENTE',
      'formatter' => null,
    ],
    'cliente_direccion' => [
      'label' => 'DIRECCIÓN CLIENTE',
      'formatter' => null,
    ],
    'cliente_email' => [
      'label' => 'EMAIL CLIENTE',
      'formatter' => null,
    ],
    'currency.description' => [
      'label' => 'MONEDA',
      'formatter' => null,
    ],
    'tipo_de_cambio' => [
      'label' => 'TIPO CAMBIO',
      'formatter' => null,
    ],
    'total_gravada' => [
      'label' => 'TOTAL GRAVADA',
      'formatter' => null,
    ],
    'total_inafecta' => [
      'label' => 'TOTAL INAFECTA',
      'formatter' => null,
    ],
    'total_exonerada' => [
      'label' => 'TOTAL EXONERADA',
      'formatter' => null,
    ],
    'total_igv' => [
      'label' => 'TOTAL IGV',
      'formatter' => null,
    ],
    'total' => [
      'label' => 'TOTAL',
      'formatter' => null,
    ],
    'status' => [
      'label' => 'ESTADO',
      'formatter' => null,
    ],
    'aceptada_por_sunat' => [
      'label' => 'ACEPTADA SUNAT',
      'formatter' => 'boolean',
    ],
    'purchaseRequestQuote.internal_code' => [
      'label' => 'CÓDIGO COTIZACIÓN',
      'formatter' => null,
    ],
    'purchaseRequestQuote.opportunity.opportunity_code' => [
      'label' => 'CÓDIGO OPORTUNIDAD',
      'formatter' => null,
    ],
    'purchaseRequestQuote.opportunity.worker.nombre_completo' => [
      'label' => 'ASESOR',
      'formatter' => null,
    ],
    'vehicle.vin' => [
      'label' => 'VIN',
      'formatter' => null,
    ],
    'vehicle.plate' => [
      'label' => 'PLACA',
      'formatter' => null,
    ],
    'vehicle.engine_number' => [
      'label' => 'NRO MOTOR',
      'formatter' => null,
    ],
    'vehicle.year' => [
      'label' => 'AÑO',
      'formatter' => null,
    ],
    'vehicle.model.family.brand.name' => [
      'label' => 'MARCA',
      'formatter' => null,
    ],
    'vehicle.model.family.description' => [
      'label' => 'MODELO',
      'formatter' => null,
    ],
    'vehicle.model.version' => [
      'label' => 'VERSIÓN',
      'formatter' => null,
    ],
    'vehicle.color.description' => [
      'label' => 'COLOR',
      'formatter' => null,
    ],
    'vehicleMovement.vehicle.warehousePhysical.dyn_code' => [
      'label' => 'ALMACÉN',
      'formatter' => null,
    ],
    'vehicleMovement.vehicle.warehousePhysical.description' => [
      'label' => 'DESCRIPCIÓN ALMACÉN',
      'formatter' => null,
    ],
    'seriesModel.sede.suc_abrev' => [
      'label' => 'SEDE',
      'formatter' => null,
    ],
    'seriesModel.sede.shop.description' => [
      'label' => 'TIENDA',
      'formatter' => null,
    ],
    'orderQuotation.code' => [
      'label' => 'CÓDIGO COTIZACIÓN POSVENTA',
      'formatter' => null,
    ],
    'workOrder.workorder_number' => [
      'label' => 'ORDEN DE TRABAJO',
      'formatter' => null,
    ],
    'condiciones_de_pago' => [
      'label' => 'CONDICIONES DE PAGO',
      'formatter' => null,
    ],
    'observaciones' => [
      'label' => 'OBSERVACIONES',
      'formatter' => null,
    ],
    'creator.name' => [
      'label' => 'CREADO POR',
      'formatter' => null,
    ],
    'created_at' => [
      'label' => 'FECHA CREACIÓN',
      'formatter' => 'datetime',
    ],
  ];

  /**
   * Relaciones a cargar para reportes
   */
  protected $reportRelations = [
    'documentType',
    'identityDocumentType',
    'currency',
    'seriesModel.sede.shop',
    'purchaseRequestQuote.opportunity.worker',
    'vehicle.model.family.brand',
    'vehicle.color',
    'vehicleMovement.vehicle.warehousePhysical',
    'orderQuotation',
    'workOrder',
    'creator',
  ];
}
