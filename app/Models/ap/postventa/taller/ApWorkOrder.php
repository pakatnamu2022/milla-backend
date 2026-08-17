<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Services\ap\postventa\taller\WorkOrderAdvancePaymentService;
use App\Http\Services\ap\postventa\taller\WorkOrderBillingService;
use App\Http\Services\ap\postventa\taller\WorkOrderDocumentService;
use App\Http\Traits\HasExchangeRateToUsd;
use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApWorkOrder extends Model
{
  use SoftDeletes, HasExchangeRateToUsd;

  // Lazy-loaded service instances (singleton pattern per model instance)
  private ?WorkOrderDocumentService $documentService = null;
  private ?WorkOrderAdvancePaymentService $advancePaymentService = null;
  private ?WorkOrderBillingService $billingService = null;

  protected $table = 'ap_work_orders';

  protected $fillable = [
    'correlative',
    'appointment_planning_id',
    'order_quotation_id',
    'vehicle_id',
    'currency_id',
    'vehicle_plate',
    'vehicle_vin',
    'mileage',
    'status_id',
    'advisor_id',
    'invoice_to',
    'sede_id',
    'exchange_rate_id',
    'exchange_rate',
    'num_doc_contact',
    'full_contact_name',
    'phone_contact',
    'num_doc_pickup',
    'full_pickup_name',
    'phone_pickup',
    'opening_date',
    'estimated_delivery_date',
    'estimated_delivery_time',
    'actual_delivery_date',
    'official_closing_date',
    'is_delivery',
    'delivery_by',
    'diagnosis_date',
    'observations',
    'total_labor_cost',
    'total_parts_cost',
    'subtotal',
    'discount_percentage',
    'discount_amount',
    'tax_amount',
    'final_amount',
    'deductible_amount',
    'is_invoiced',
    'is_guarantee',
    'is_recall',
    'description_recall',
    'type_recall',
    'has_invoice_generated',
    'output_generation_warehouse',
    'internal_note_revert_count',
    'internal_note_total_reverts',
    'internal_note_revert_authorized_by',
    'internal_note_revert_authorized_at',
    'allow_remove_associated_quote',
    'allow_editing_inspection',
    'created_by',
    'post_service_follow_up',
    'signature_delivery_url',
    'notes_delivery',
    'discard_reason_id',
    'discarded_note',
    'discarded_by',
    'discarded_at',
    'is_sold_at_valid_price',
  ];

  protected $casts = [
    'opening_date' => 'date',
    'estimated_delivery_date' => 'datetime',
    'estimated_delivery_time' => 'datetime:H:i',
    'actual_delivery_date' => 'datetime',
    'official_closing_date' => 'datetime',
    'is_delivery' => 'boolean',
    'diagnosis_date' => 'datetime',
    'is_invoiced' => 'boolean',
    'is_guarantee' => 'boolean',
    'is_recall' => 'boolean',
    'has_invoice_generated' => 'boolean',
    'output_generation_warehouse' => 'boolean',
    'internal_note_revert_count' => 'integer',
    'internal_note_total_reverts' => 'integer',
    'internal_note_revert_authorized_at' => 'datetime',
    'total_labor_cost' => 'decimal:2',
    'total_parts_cost' => 'decimal:2',
    'subtotal' => 'decimal:2',
    'discount_percentage' => 'decimal:2',
    'discount_amount' => 'decimal:2',
    'tax_amount' => 'decimal:2',
    'final_amount' => 'decimal:2',
    'deductible_amount' => 'decimal:2',
    'allow_remove_associated_quote' => 'boolean',
    'allow_editing_inspection' => 'boolean',
    'post_service_follow_up' => 'array',
    'discarded_at' => 'datetime',
    'is_sold_at_valid_price' => 'boolean',
  ];

  const filters = [
    'search' => ['correlative', 'vehicle_plate', 'vehicle_vin', 'observations', 'internalNotes.number'],
    'correlative' => '=',
    'currency_id' => '=',
    'appointment_planning_id' => '=',
    'order_quotation_id' => '=',
    'vehicle_id' => '=',
    'vehicle_plate' => 'like',
    'vehicle_vin' => 'like',
    'status_id' => 'in_or_equal',
    'advisor_id' => '=',
    'sede_id' => '=',
    'opening_date' => 'date_between',
    'estimated_delivery_date' => 'date_between',
    'actual_delivery_date' => 'between',
    'diagnosis_date' => 'between',
    'is_invoiced' => '=',
    'created_by' => '=',
    'items.typePlanning.id' => '=',
    'items.typePlanning.type_document' => '='
  ];

  const sorts = [
    'id',
    'correlative',
    'opening_date',
    'estimated_delivery_date',
    'estimated_delivery_time',
    'actual_delivery_date',
    'created_at',
  ];

  const int LIMITATION_PERMITTED_REVERSAL = 1;

  // Boot method
  protected static function boot()
  {
    parent::boot();

    // When deleting a work order, also delete its details
    static::deleting(function ($reception) {
      $reception->items()->delete();
    });
  }

  // Mutators
  public function setFullContactNameAttribute($value)
  {
    if ($value) {
      $this->attributes['full_contact_name'] = Str::upper($value);
    }
  }

  public function setFullPickupNameAttribute($value)
  {
    if ($value) {
      $this->attributes['full_pickup_name'] = Str::upper($value);
    }
  }

  public function setObservationsAttribute($value)
  {
    if ($value) {
      $this->attributes['observations'] = Str::upper($value);
    }
  }

  public function setVehiclePlateAttribute($value)
  {
    if ($value) {
      $this->attributes['vehicle_plate'] = Str::upper($value);
    }
  }

  public function setVehicleVinAttribute($value)
  {
    if ($value) {
      $this->attributes['vehicle_vin'] = Str::upper($value);
    }
  }

  public function setDiscardedNoteAttribute($value)
  {
    if ($value) {
      $this->attributes['discarded_note'] = Str::upper($value);
    }
  }

  // Service Helpers (lazy-loaded singletons per instance)
  protected function documentService(): WorkOrderDocumentService
  {
    if ($this->documentService === null) {
      $this->documentService = app(WorkOrderDocumentService::class);
    }
    return $this->documentService;
  }

  protected function advancePaymentService(): WorkOrderAdvancePaymentService
  {
    if ($this->advancePaymentService === null) {
      $this->advancePaymentService = app(WorkOrderAdvancePaymentService::class);
    }
    return $this->advancePaymentService;
  }

  protected function billingService(): WorkOrderBillingService
  {
    if ($this->billingService === null) {
      $this->billingService = app(WorkOrderBillingService::class);
    }
    return $this->billingService;
  }

  // Accessors
  public function getDeductibleAmountWithoutTaxAttribute()
  {
    if (!$this->deductible_amount) {
      return 0;
    }

    return (float)($this->deductible_amount / (1 + Constants::VAT_TAX / 100));
  }

  // Relations
  public function appointmentPlanning(): BelongsTo
  {
    return $this->belongsTo(AppointmentPlanning::class, 'appointment_planning_id');
  }

  public function orderQuotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'order_quotation_id');
  }

  public function vehicle(): BelongsTo
  {
    return $this->belongsTo(Vehicles::class, 'vehicle_id');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function status(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'status_id');
  }

  public function advisor(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'advisor_id');
  }

  public function invoiceTo(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'invoice_to');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function deliveryBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'delivery_by');
  }

  public function items(): HasMany
  {
    return $this->hasMany(ApWorkOrderItem::class, 'work_order_id');
  }

  public function plannings(): HasMany
  {
    return $this->hasMany(ApWorkOrderPlanning::class, 'work_order_id');
  }

  public function labours(): HasMany
  {
    return $this->hasMany(WorkOrderLabour::class, 'work_order_id');
  }

  public function parts(): HasMany
  {
    return $this->hasMany(ApWorkOrderParts::class, 'work_order_id');
  }

  /**
   * Obtiene todos los registros pivot de inspecciones de esta orden
   */
  public function vehicleInspectionPivots(): HasMany
  {
    return $this->hasMany(WorkOrderVehicleInspection::class, 'work_order_id');
  }

  /**
   * Relación para obtener la inspección vehicular activa (no cancelada)
   * Usa hasOneThrough para soportar eager loading
   */
  public function vehicleInspection(): HasOneThrough
  {
    return $this->hasOneThrough(
      ApVehicleInspection::class,
      WorkOrderVehicleInspection::class,
      'work_order_id',      // Foreign key en work_order_vehicle_inspection
      'id',                 // Foreign key en ap_vehicle_inspections
      'id',                 // Local key en ap_work_orders
      'vehicle_inspection_id' // Local key en work_order_vehicle_inspection
    )->where('work_order_vehicle_inspection.is_cancelled', false)
      ->latest('work_order_vehicle_inspection.id');
  }

  /**
   * Método helper para obtener la inspección activa (backward compatibility)
   * @deprecated Usar la relación vehicleInspection() directamente
   * @return ApVehicleInspection|null
   */
  public function getActiveVehicleInspection(): ?ApVehicleInspection
  {
    return $this->vehicleInspection;
  }

  /**
   * Verifica si tiene al menos una inspección activa (no cancelada)
   * @param int|null $vehicleInspectionId ID de inspección específica para validar (opcional)
   * @return bool
   */
  public function hasActiveInspection(?int $vehicleInspectionId = null): bool
  {
    $query = $this->vehicleInspectionPivots()
      ->where('is_cancelled', false);

    // Si se especifica un ID de inspección, filtrar por ese
    if ($vehicleInspectionId !== null) {
      $query->where('vehicle_inspection_id', $vehicleInspectionId);
    }

    return $query->exists();
  }

  public function exchangeRate(): BelongsTo
  {
    return $this->belongsTo(ExchangeRate::class, 'exchange_rate_id');
  }

  public function discardReason(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'discard_reason_id');
  }

  public function discardedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'discarded_by');
  }

  public function deductibles(): HasMany
  {
    return $this->hasMany(ApDeductibleWorkOrder::class, 'work_order_id');
  }

  public function internalNotes(): HasMany
  {
    return $this->hasMany(ApInternalNote::class, 'work_order_id');
  }

  // Helper methods
  public function calculateTotals(): void
  {
    $totals = $this->getTotalsArray();

    // Actualizar los campos de la orden de trabajo. Redondeo en cadena a 2 decimales:
    // los ítems de repuestos/mano de obra (y los pendientes de cotización) ya llegan
    // redondeados a 2 decimales, pero se vuelve a redondear aquí por seguridad ante
    // arrastre de precisión flotante al sumar varios ítems.
    $this->total_labor_cost = round($totals['labour_cost'], 2);
    $this->total_parts_cost = round($totals['parts_cost'], 2);
    $this->subtotal = round($totals['total_cost'], 2);
    $this->discount_amount = round($totals['discount_amount'], 2);
    $this->tax_amount = round($totals['tax_amount'], 2);
    $this->final_amount = round($totals['total_amount'], 2);

    // Calcular discount_percentage basado en el discount_amount y subtotal
    if ($totals['total_cost'] > 0) {
      $this->discount_percentage = round(($totals['discount_amount'] / $totals['total_cost']) * 100, 2);
    } else {
      $this->discount_percentage = 0;
    }

    $this->save();
  }

  /**
   * Calcula si todas las partes se vendieron al precio mínimo o superior
   */
  public function calculateIsSoldAtValidPrice(): void
  {
    $parts = $this->parts()
      ->whereNotNull('product_id')
      ->get();

    if ($parts->isEmpty()) {
      $this->is_sold_at_valid_price = true;
      return;
    }

    foreach ($parts as $part) {
      if ($part->is_traverse) continue;

      if (!$part->sale_price_min_original || $part->sale_price_min_original == 0) {
        continue;
      }

      $unitPriceInSoles = $part->unit_price;
      if ($this->currency_id === TypeCurrency::USD_ID && $this->exchange_rate) {
        $unitPriceInSoles = $part->unit_price * $this->exchange_rate;
      }

      if ($unitPriceInSoles < $part->sale_price_min_original) {
        $this->is_sold_at_valid_price = false;
        return;
      }
    }

    $this->is_sold_at_valid_price = true;
  }

  /**
   * Obtiene los totales calculados sin guardar en la base de datos
   */
  public function getTotalsArray(?int $groupNumber = null): array
  {
    // Si tiene cotización asociada, usar el cálculo que incluye items pendientes de la cotización
    if ($this->order_quotation_id && $this->orderQuotation) {
      return $this->calculateTotalsWithQuotation($groupNumber);
    } else {
      // Cálculo tradicional sin cotización
      return $this->calculateTotalsWithoutQuotation($groupNumber);
    }
  }

  /**
   * Calcula totales SIN considerar cotización (solo labours y parts existentes)
   */
  private function calculateTotalsWithoutQuotation(?int $groupNumber = null): array
  {
    // Filter labours by group_number if provided
    $laboursQuery = $this->labours();
    $partsQuery = $this->parts();

    if ($groupNumber !== null) {
      $laboursQuery->where('group_number', $groupNumber);
      $partsQuery->where('group_number', $groupNumber);
    }

    // Calculate costs (sin descuento de items)
    $totalLabourCostBeforeDiscount = $laboursQuery->sum('total_cost') ?? 0;
    $totalPartsCostBeforeDiscount = $partsQuery->sum('total_cost') ?? 0;

    // Calculate net amounts (con descuento de items aplicado)
    $totalLabourNetAmount = $this->labours()->when($groupNumber !== null, function ($q) use ($groupNumber) {
      return $q->where('group_number', $groupNumber);
    })->sum('net_amount') ?? 0;

    $totalPartsNetAmount = $this->parts()->when($groupNumber !== null, function ($q) use ($groupNumber) {
      return $q->where('group_number', $groupNumber);
    })->sum('net_amount') ?? 0;

    // Sumar tax_amount de todos los items (ya calculados a nivel de item)
    $totalLabourTaxAmount = $this->labours()->when($groupNumber !== null, function ($q) use ($groupNumber) {
      return $q->where('group_number', $groupNumber);
    })->sum('tax_amount') ?? 0;

    $totalPartsTaxAmount = $this->parts()->when($groupNumber !== null, function ($q) use ($groupNumber) {
      return $q->where('group_number', $groupNumber);
    })->sum('tax_amount') ?? 0;

    // Subtotal sin descuentos
    $subtotal = $totalLabourCostBeforeDiscount + $totalPartsCostBeforeDiscount;

    // Total de descuentos de items (la diferencia entre total_cost y net_amount)
    $itemsDiscountAmount = ($totalLabourCostBeforeDiscount - $totalLabourNetAmount) + ($totalPartsCostBeforeDiscount - $totalPartsNetAmount);

    // Net amount (suma de net_amount de items)
    $netAmount = $totalLabourNetAmount + $totalPartsNetAmount;

    // Tax amount (suma de tax_amount de items - ya no se calcula, se suma directamente)
    $taxAmount = $totalLabourTaxAmount + $totalPartsTaxAmount;

    // Total final (suma de net_amount + suma de tax_amount)
    $totalAmount = $netAmount + $taxAmount;

    return [
      'labour_cost' => (float)$totalLabourCostBeforeDiscount,
      'parts_cost' => (float)$totalPartsCostBeforeDiscount,
      'labour_cost_desc' => (float)$totalLabourNetAmount,
      'parts_cost_desc' => (float)$totalPartsNetAmount,
      'total_cost' => (float)$subtotal,
      'net_amount' => (float)$netAmount,
      'discount_amount' => (float)$itemsDiscountAmount,
      'tax_amount' => (float)$taxAmount,
      'total_amount' => (float)$totalAmount,
    ];
  }

  /**
   * Calcula totales INCLUYENDO items pendientes de la cotización
   */
  private function calculateTotalsWithQuotation(?int $groupNumber = null): array
  {
    // Filter labours by group_number if provided
    $labours = $groupNumber !== null
      ? $this->labours->where('group_number', $groupNumber)
      : $this->labours;

    // Filter parts by group_number if provided
    $parts = $groupNumber !== null
      ? $this->parts->where('group_number', $groupNumber)
      : $this->parts;

    // Sumar total_cost, net_amount y tax_amount de labours existentes
    $totalLabourCostBeforeDiscount = $labours->sum('total_cost') ?? 0;
    $totalLabourNetAmount = $labours->sum('net_amount') ?? 0;
    $totalLabourTaxAmount = $labours->sum('tax_amount') ?? 0;

    // Sumar total_cost, net_amount y tax_amount de parts existentes
    $totalPartsCostBeforeDiscount = $parts->sum('total_cost') ?? 0;
    $totalPartsNetAmount = $parts->sum('net_amount') ?? 0;
    $totalPartsTaxAmount = $parts->sum('tax_amount') ?? 0;

    // Add pending quotation details (solo si no se filtró por group_number, ya que la cotización no tiene group_number)
    if ($groupNumber === null && $this->orderQuotation && $this->orderQuotation->details) {
      $pendingDetails = $this->orderQuotation->details
        ->where('status', ApOrderQuotationDetails::STATUS_PENDING);

      foreach ($pendingDetails as $detail) {
        // Redondeo en cadena a 2 decimales, igual que en los repuestos/mano de obra
        // ya cargados a la OT, para que al asociar una cotización el total no rompa esa regla.
        $itemTotalCost = round((float)($detail->total_cost ?? 0), 2);
        $itemNetAmount = round((float)($detail->net_amount ?? 0), 2);
        $itemTaxAmount = round((float)($detail->tax_amount ?? 0), 2);

        if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
          $totalLabourCostBeforeDiscount += $itemTotalCost;
          $totalLabourNetAmount += $itemNetAmount;
          $totalLabourTaxAmount += $itemTaxAmount;
        } elseif ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_PRODUCT) {
          $totalPartsCostBeforeDiscount += $itemTotalCost;
          $totalPartsNetAmount += $itemNetAmount;
          $totalPartsTaxAmount += $itemTaxAmount;
        }
      }
    }

    // Subtotal sin descuentos
    $subtotal = $totalLabourCostBeforeDiscount + $totalPartsCostBeforeDiscount;

    // Total de descuentos de items (la diferencia entre total_cost y net_amount)
    $itemsDiscountAmount = ($totalLabourCostBeforeDiscount - $totalLabourNetAmount) + ($totalPartsCostBeforeDiscount - $totalPartsNetAmount);

    // Net amount (suma de net_amount de items)
    $netAmount = $totalLabourNetAmount + $totalPartsNetAmount;

    // Tax amount (suma de tax_amount de items - ya no se calcula, se suma directamente)
    $taxAmount = $totalLabourTaxAmount + $totalPartsTaxAmount;

    // Total final (suma de net_amount + suma de tax_amount)
    $totalAmount = $netAmount + $taxAmount;

    return [
      'labour_cost' => (float)$totalLabourCostBeforeDiscount,
      'parts_cost' => (float)$totalPartsCostBeforeDiscount,
      'labour_cost_desc' => (float)$totalLabourNetAmount,
      'parts_cost_desc' => (float)$totalPartsNetAmount,
      'total_cost' => (float)$subtotal,
      'net_amount' => (float)$netAmount,
      'discount_amount' => (float)$itemsDiscountAmount,
      'tax_amount' => (float)$taxAmount,
      'total_amount' => (float)$totalAmount,
    ];
  }

  public function advancesWorkOrder(): HasMany
  {
    return $this->hasMany(ElectronicDocument::class, 'work_order_id');
  }

  /**
   * @see HasExchangeRateToUsd
   */
  public function exchangeRateDocuments(): HasMany
  {
    return $this->advancesWorkOrder();
  }

  public function discountRequests()
  {
    return $this->hasMany(DiscountRequestsWorkOrder::class, 'ap_work_order_id');
  }

  public function internalNote(): HasOne
  {
    return $this->hasOne(ApInternalNote::class, 'work_order_id');
  }

  public function internalNoteRevertAuthorizedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'internal_note_revert_authorized_by');
  }

  /**
   * Obtiene labours y parts dinámicamente:
   * - Si NO tiene cotización: retorna labours y parts existentes
   * - Si SÍ tiene cotización: retorna labours y parts existentes + items pendientes de la cotización
   *
   * @param int|null $groupNumber Filtro opcional por número de grupo
   * @return array{labours: \Illuminate\Support\Collection, parts: \Illuminate\Support\Collection}
   */
  public function getDynamicItemsForInvoicing(?int $groupNumber = null): array
  {
    return $this->billingService()->getDynamicItemsForInvoicing($this, $groupNumber);
  }

  /**
   * Obtiene el flag de validación de labor del tipo de
   * planificación
   */
  public function shouldValidateLabor(): bool
  {
    return (bool)
    $this->items->first()?->typePlanning->validate_labor;
  }

  /**
   * Obtiene el flag de validación de recepción del tipo de
   * planificación
   */
  public function shouldValidateReceipt(): bool
  {
    return (bool)
    $this->items->first()?->typePlanning->validate_receipt;
  }

  /**
   * Verifica si la orden de trabajo tiene un tipo de documento específico
   *
   * @param string $typeDocument El tipo de documento a verificar (ej: TypePlanningWorkOrder::INTERNA_SC)
   * @return bool
   */
  public function hasTypeDocument(string $typeDocument): bool
  {
    return $this->items()
      ->whereHas('typePlanning', function ($query) use ($typeDocument) {
        $query->where('type_document', $typeDocument);
      })->exists();
  }

  /**
   * Verifica si la orden de trabajo es de tipo INTERNA_SC
   *
   * @return bool
   */
  public function isInternaSC(): bool
  {
    return $this->hasTypeDocument(TypePlanningWorkOrder::INTERNA_SC);
  }

  /**
   * Verifica si la orden de trabajo es de tipo INTERNA_CC
   *
   * @return bool
   */
  public function isInternaCC(): bool
  {
    return $this->hasTypeDocument(TypePlanningWorkOrder::INTERNA_CC);
  }

  /**
   * Verifica si la orden de trabajo es de tipo PAYMENT_RECEIPTS
   *
   * @return bool
   */
  public function isPaymentReceipts(): bool
  {
    return $this->hasTypeDocument(TypePlanningWorkOrder::PAYMENT_RECEIPTS);
  }

  /**
   * Verifica si la orden de trabajo es de tipo interno (INTERNA_SC o INTERNA_CC)
   *
   * @return bool
   */
  public function isInternalType(): bool
  {
    return $this->items()
      ->whereHas('typePlanning', function ($query) {
        $query->whereIn('type_document', [
          TypePlanningWorkOrder::INTERNA_SC,
          TypePlanningWorkOrder::INTERNA_CC
        ]);
      })->exists();
  }

  /**
   * Check if there's a draft advance payment for this work order.
   *
   * @return bool
   */
  public function hasDraftAdvance(): bool
  {
    return $this->documentService()->hasDraftAdvance($this);
  }

  /**
   * Check if there's a draft final invoice for this work order.
   *
   * @return bool
   */
  public function hasDraftFinalInvoice(): bool
  {
    return $this->documentService()->hasDraftFinalInvoice($this);
  }

  /**
   * Get active advances for this work order.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getActiveAdvances()
  {
    return $this->documentService()->getActiveAdvances($this);
  }

  /**
   * Get cancelled advances for this work order.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getCancelledAdvances()
  {
    return $this->documentService()->getCancelledAdvances($this);
  }

  /**
   * Get the final invoice (factura/boleta final) for this work order.
   *
   * @return \Closure
   */
  public function getFinalInvoice()
  {
    return $this->documentService()->getFinalInvoice($this);
  }

  /**
   * Check if there's a final invoice already generated and accepted.
   * Optimized for validation - uses the service method.
   *
   * @return bool
   */
  public function hasFinalInvoice(): bool
  {
    return $this->getFinalInvoice() !== null;
  }

  /**
   * Check if there are any active advances for this work order.
   * Optimized for validation - uses exists() instead of loading all records.
   *
   * @return bool
   */
  public function hasAdvances(): bool
  {
    return $this->documentService()->hasActiveAdvances($this);
  }

  /**
   * Get all valid documents for this work order (advances + final invoice).
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getValidDocuments()
  {
    return $this->documentService()->getValidDocuments($this);
  }

  /**
   * Obtiene el monto neto pagado en anticipos activos
   * Considera notas de crédito y débito sobre los anticipos
   * (suma de anticipos - NC parciales + ND sobre esos anticipos)
   *
   * @return float
   */
  public function getNetAmountFromAdvances(): float
  {
    return $this->advancePaymentService()->getNetAmountFromAdvances($this);
  }


  /**
   * Obtiene el subtotal (sin IGV) de los anticipos activos
   * Usa directamente los subtotales almacenados en los documentos electrónicos
   * para evitar problemas de redondeo por divisiones
   *
   * @return float
   */
  public function getSubtotalFromAdvances(): float
  {
    return $this->advancePaymentService()->getSubtotalFromAdvances($this);
  }


  /**
   * Valida que el nuevo final_amount no sea menor al monto ya pagado en anticipos
   * Se usa antes de permitir ediciones que reduzcan el monto de la OT
   *
   * @param float $newFinalAmount El nuevo monto total proyectado de la OT
   * @throws \Exception Si el nuevo monto es menor al ya pagado
   */
  public function validateMinimumAmount(float $newFinalAmount): void
  {
    $this->advancePaymentService()->validateMinimumAmount($this, $newFinalAmount);
  }

  /**
   * Valida que un descuento solicitado no reduzca el monto total por debajo de los anticipos pagados
   * Simula la aplicación del descuento y verifica contra los anticipos
   *
   * @param string $type GLOBAL o PARTIAL
   * @param string $partLabourModel ApWorkOrderParts::class o WorkOrderLabour::class
   * @param float $discountPercentage Porcentaje de descuento a aplicar
   * @param int|null $partLabourId ID del ítem específico (solo para PARTIAL)
   * @throws \Exception Si el descuento reduce el monto por debajo de los anticipos pagados
   */
  public function validateDiscountAgainstAdvances(
    string $type,
    string $partLabourModel,
    float  $discountPercentage,
    ?int   $partLabourId = null
  ): void
  {
    $this->advancePaymentService()->validateDiscountAgainstAdvances(
      $this,
      $type,
      $partLabourModel,
      $discountPercentage,
      $partLabourId
    );
  }

  /**
   * Valida que la orden de trabajo NO esté en los estados prohibidos
   *
   * @param array $forbiddenStatuses Estados a validar
   * @param string|null $action Acción que se está intentando (opcional, para personalizar mensaje)
   * @throws \Exception
   */
  public function ensureNotInStates(array $forbiddenStatuses, ?string $action = null): void
  {
    if (in_array($this->status_id, $forbiddenStatuses, true)) {
      $statusNames = [
        ApMasters::CANCELED_WORK_ORDER_ID => 'anulada',
        ApMasters::FINISHED_WORK_ORDER_ID => 'finalizada',
        ApMasters::CLOSED_WORK_ORDER_ID => 'cerrada',
      ];

      $statusName = $statusNames[$this->status_id] ?? 'este estado';
      $message = $action
        ? "No se puede {$action} en una orden de trabajo {$statusName}"
        : "Esta acción no está permitida en una orden de trabajo {$statusName}";

      throw new \Exception($message);
    }
  }

  /**
   * Valida que la orden de trabajo esté en uno de los estados permitidos
   *
   * @param array $allowedStatuses Estados permitidos
   * @param string|null $action Acción que se está intentando (opcional, para personalizar mensaje)
   * @throws \Exception
   */
  public function ensureInStates(array $allowedStatuses, ?string $action = null): void
  {
    if (!in_array($this->status_id, $allowedStatuses, true)) {
      $statusNames = [
        ApMasters::CANCELED_WORK_ORDER_ID => 'anulada',
        ApMasters::FINISHED_WORK_ORDER_ID => 'finalizada',
        ApMasters::CLOSED_WORK_ORDER_ID => 'cerrada',
        ApMasters::OPENING_WORK_ORDER_ID => 'abierta',
        ApMasters::RECEIVED_WORK_ORDER_ID => 'recepcionada',
        ApMasters::AT_WORK_WORK_ORDER_ID => 'en trabajo',
        ApMasters::END_WORK_WORK_ORDER_ID => 'trabajo finalizado',
      ];

      $currentStatusName = $statusNames[$this->status_id] ?? 'este estado';
      $message = $action
        ? "No se puede {$action} en una orden de trabajo {$currentStatusName}"
        : "Esta acción no está permitida en una orden de trabajo {$currentStatusName}";

      throw new \Exception($message);
    }
  }

  /**
   * Valida que la orden de trabajo pueda ser modificada
   * (no esté anulada, finalizada o cerrada)
   *
   * @throws \Exception
   */
  public function ensureCanBeModified(): void
  {
    $this->ensureNotInStates([
      ApMasters::CANCELED_WORK_ORDER_ID,
      ApMasters::FINISHED_WORK_ORDER_ID,
      ApMasters::CLOSED_WORK_ORDER_ID,
    ]);
  }

  /**
   * Get all documents organized in a tree structure with cancelled and active documents.
   * Active documents include their credit/debit note modifications.
   *
   * @return array
   */
  public function getDocumentsTree(): array
  {
    return $this->documentService()->getDocumentsTree($this);
  }

  /**
   * Get payment summary information for this work order.
   *
   * Returns only payment-related information without duplicating data already
   * available in the WorkOrder resource header (final_amount, subtotal, etc.)
   *
   * Uses rounding tolerance to account for IGV calculation differences.
   *
   * @return array
   */
  public function getPaymentSummary(): array
  {
    return $this->documentService()->getPaymentSummary($this, $this->advancePaymentService());
  }

  /**
   * Construye el detalle de facturación (items_invoice) y sus totales (invoice_preview)
   * para que el frontend deje de recalcular esto por su cuenta.
   *
   * Reutiliza los montos ya persistidos por item (net_amount/tax_amount/total_cost) y
   * el mismo neto de anticipos usado en getPaymentSummary(), así todo cuadra entre sí.
   *
   * @return array{items_invoice: array, invoice_preview: array}
   */
  public function getInvoicePreview(): array
  {
    return $this->billingService()->getInvoicePreviewForWorkOrder($this);
  }

  /**
   * Actualiza la fecha de cierre oficial de la OT según el tipo de documento
   *
   * IMPORTANTE: Este método SIEMPRE actualiza la fecha cuando se llama explícitamente.
   * Esto permite manejar casos donde:
   * - Se emite una factura final → se establece la fecha
   * - Se anula esa factura → getFinalInvoice() retorna null
   * - Se emite una NUEVA factura → la fecha se ACTUALIZA con la nueva emisión
   *
   * @param \DateTime|string|null $date Fecha de cierre oficial (si es null, usa now())
   * @return bool True si se actualizó, false si no se debe actualizar según el tipo de documento
   */
  public function updateOfficialClosingDate(\DateTime|string|null $date = null): bool
  {
    // Obtener el tipo de documento del primer item
    $typeDocument = $this->items->first()?->typePlanning->type_document;

    // Solo actualizar si es uno de los tipos que requieren fecha de cierre oficial:
    // 1. INTERNA_SC (sin comprobante) - se cierra al generar la nota interna
    // 2. INTERNA_CC (con comprobante) - se cierra al emitir el comprobante
    // 3. PAYMENT_RECEIPTS - se cierra al emitir el comprobante final
    if (!in_array($typeDocument, [
      TypePlanningWorkOrder::INTERNA_SC,
      TypePlanningWorkOrder::INTERNA_CC,
      TypePlanningWorkOrder::PAYMENT_RECEIPTS
    ])) {
      return false;
    }

    // Establecer/actualizar la fecha de cierre oficial
    // NOTA: Permite sobrescribir para manejar casos de anulación y re-emisión
    $this->official_closing_date = $date ?? now();
    $this->save();

    return true;
  }

  // Export Methods
  public static function getReportData($filters = [])
  {
    $query = self::with([
      'vehicle.model.family.brand',
      'vehicle.model',
      'advisor',
      'sede',
      'status',
      'items.typePlanning',
      'items.typeOperation',
      'creator',
      'typeCurrency',
      'invoiceTo',
      'advancesWorkOrder',
      'internalNote.electronicDocuments'
    ]);

    // Apply filters
    foreach ($filters as $filter) {
      $column = $filter['column'];
      $operator = $filter['operator'];
      $value = $filter['value'];

      if ($column === 'advisor_id' && $operator === '=') {
        $query->where('advisor_id', $value);
      } elseif ($column === 'sede_id' && $operator === '=') {
        $query->where('sede_id', $value);
      } elseif ($column === 'status_id' && $operator === 'in_or_equal') {
        if (is_array($value)) {
          $query->whereIn('status_id', $value);
        } else {
          $query->where('status_id', $value);
        }
      } elseif ($column === 'opening_date' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('opening_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'estimated_delivery_date' && $operator === 'date_between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('estimated_delivery_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'actual_delivery_date' && $operator === 'between') {
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween('actual_delivery_date', [$value[0], $value[1]]);
        }
      } elseif ($column === 'is_invoiced' && $operator === '=') {
        $query->where('is_invoiced', $value);
      } elseif ($column === 'currency_id' && $operator === '=') {
        $query->where('currency_id', $value);
      } elseif ($column === 'vehicle_plate' && $operator === 'like') {
        $query->where('vehicle_plate', 'like', '%' . $value . '%');
      }
    }

    $workOrders = $query->get();

    return $workOrders->map(function ($workOrder) {
      // Obtener el primer item no eliminado
      $firstItem = $workOrder->items->first();

      // Obtener los anticipos activos
      $activeAdvances = $workOrder->getActiveAdvances();

      // Formatear anticipos: "full_number (SI/NO) | full_number (SI/NO)"
      $advancesFormatted = '-';
      $totalAdvances = 0;
      $tieneAnticipo = 'NO';

      if ($activeAdvances && $activeAdvances->count() > 0) {
        $tieneAnticipo = 'SI';
        $advancesArray = [];
        foreach ($activeAdvances as $advance) {
          $sunatStatus = $advance->aceptada_por_sunat == 1 ? '(SI)' : '(NO)';
          $advancesArray[] = $advance->full_number . ' ' . $sunatStatus;
          $totalAdvances += $advance->total ?? 0;
        }
        $advancesFormatted = implode(' | ', $advancesArray);
      }

      // Determinar el documento electrónico a usar para las últimas 3 columnas
      $electronicDocument = null;

      // Si el tipo de documento es INTERNA_CC, obtener el documento desde la nota interna
      if ($firstItem && $firstItem->typePlanning && $firstItem->typePlanning->type_document === TypePlanningWorkOrder::INTERNA_CC) {
        $internalNote = $workOrder->internalNote;
        if ($internalNote) {
          $electronicDocument = $internalNote->electronicDocuments()->first();
        }
      }

      // Si no se encontró documento desde nota interna, usar getFinalInvoice()
      if (!$electronicDocument) {
        $electronicDocument = $workOrder->getFinalInvoice();
      }

      return [
        'sede' => $workOrder->sede ? $workOrder->sede->abreviatura : '',
        'correlativo' => $workOrder->correlative,
        'estado' => $workOrder->status ? $workOrder->status->description : '',
        'fecha_apertura' => $workOrder->opening_date ? $workOrder->opening_date->format('Y-m-d') : '',
        'fecha_entrega_estimada' => $workOrder->estimated_delivery_date ? $workOrder->estimated_delivery_date->format('Y-m-d H:i:s') : '',
        'placa_vehiculo' => $workOrder->vehicle_plate,
        'vin_vehiculo' => $workOrder->vehicle_vin,
        'marca' => $workOrder->vehicle?->model?->family?->brand?->name ?? '',
        'modelo' => $workOrder->vehicle?->model?->version ?? '',
        'km' => $workOrder->mileage ?? '',
        'asesor' => $workOrder->advisor ? $workOrder->advisor->nombre_completo : '',
        'tipo_planificacion' => $firstItem && $firstItem->typePlanning ? $firstItem->typePlanning->description : '',
        'operacion' => $firstItem && $firstItem->typeOperation ? $firstItem->typeOperation->description : '',
        'descripcion_item' => $firstItem ? $firstItem->description : '',
        'moneda' => $workOrder->typeCurrency ? $workOrder->typeCurrency->symbol : '',
        'cliente_facturar' => $workOrder->invoiceTo ? $workOrder->invoiceTo->full_name : '',
        'total' => number_format($workOrder->final_amount ?? 0, 2),
        'tiene_anticipo' => $tieneAnticipo,
        'anticipos' => $advancesFormatted,
        'total_anticipos' => number_format($totalAdvances, 2),
        'comprobante_final' => $electronicDocument?->full_number ?? '-',
        'estado_sunat' => $electronicDocument ? ($electronicDocument->aceptada_por_sunat ? 'SI' : 'NO') : '-',
        'contabilizada' => $electronicDocument ? ($electronicDocument->is_accounted ? 'SI' : 'NO') : '-',
      ];
    });
  }

  public static function getReportableColumns()
  {
    return [
      'sede' => 'Sede',
      'correlativo' => 'Correlativo',
      'estado' => 'Estado',
      'fecha_apertura' => 'Fecha Apertura',
      'fecha_entrega_estimada' => 'Fecha Entrega Estimada',
      'placa_vehiculo' => 'Placa Vehículo',
      'vin_vehiculo' => 'VIN Vehículo',
      'marca' => 'Marca',
      'modelo' => 'Modelo',
      'km' => 'KM',
      'asesor' => 'Asesor',
      'tipo_planificacion' => 'Tipo de Planificación',
      'operacion' => 'Operación',
      'descripcion_item' => 'Descripción',
      'moneda' => 'Moneda',
      'cliente_facturar' => 'Cliente Facturar',
      'total' => 'Total',
      'tiene_anticipo' => 'Tiene Anticipo',
      'anticipos' => 'Anticipos',
      'total_anticipos' => 'Total Anticipos',
      'comprobante_final' => 'Comprobante Final',
      'estado_sunat' => 'Estado SUNAT',
      'contabilizada' => 'Contabilizada',
    ];
  }

  public static function getReportStyles()
  {
    return [
      'headerBackgroundColor' => '4472C4',
      'headerFontColor' => 'FFFFFF',
      'headerFontSize' => 11,
      'headerBold' => true,
      'bodyFontSize' => 10,
      'freezePane' => 'A2',
      'autoFilter' => true,
    ];
  }

  public static function getReportColorRules()
  {
    return [
      'tiene_anticipo' => [
        'SI' => ['bg' => '28A745', 'text' => 'FFFFFF'],  // Verde con texto blanco
        'NO' => ['bg' => 'DC3545', 'text' => 'FFFFFF'],  // Rojo con texto blanco
      ],
      'estado_sunat' => [
        'SI' => ['bg' => '28A745', 'text' => 'FFFFFF'],  // Verde con texto blanco
        'NO' => ['bg' => 'DC3545', 'text' => 'FFFFFF'],  // Rojo con texto blanco
      ],
      'contabilizada' => [
        'SI' => ['bg' => '28A745', 'text' => 'FFFFFF'],  // Verde con texto blanco
        'NO' => ['bg' => 'A9A9A9', 'text' => '000000'],  // Gris con texto negro
      ],
    ];
  }
}
