<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\venta\ApAccountingAccountPlan;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\ExchangeRate;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApWorkOrder extends Model
{
  use SoftDeletes;

  protected $table = 'ap_work_orders';

  protected $fillable = [
    'correlative',
    'appointment_planning_id',
    'order_quotation_id',
    'vehicle_inspection_id',
    'vehicle_id',
    'currency_id',
    'vehicle_plate',
    'vehicle_vin',
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
    'is_invoiced',
    'is_guarantee',
    'is_recall',
    'description_recall',
    'type_recall',
    'has_invoice_generated',
    'output_generation_warehouse',
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
  ];

  protected $casts = [
    'opening_date' => 'date',
    'estimated_delivery_date' => 'datetime',
    'estimated_delivery_time' => 'datetime:H:i',
    'actual_delivery_date' => 'datetime',
    'is_delivery' => 'boolean',
    'diagnosis_date' => 'datetime',
    'is_invoiced' => 'boolean',
    'is_guarantee' => 'boolean',
    'is_recall' => 'boolean',
    'has_invoice_generated' => 'boolean',
    'output_generation_warehouse' => 'boolean',
    'total_labor_cost' => 'decimal:2',
    'total_parts_cost' => 'decimal:2',
    'subtotal' => 'decimal:2',
    'discount_percentage' => 'decimal:2',
    'discount_amount' => 'decimal:2',
    'tax_amount' => 'decimal:2',
    'final_amount' => 'decimal:2',
    'allow_remove_associated_quote' => 'boolean',
    'allow_editing_inspection' => 'boolean',
    'post_service_follow_up' => 'array',
    'discarded_at' => 'datetime',
  ];

  const filters = [
    'search' => ['correlative', 'vehicle_plate', 'vehicle_vin', 'observations'],
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

  public function vehicleInspection(): BelongsTo
  {
    return $this->belongsTo(ApVehicleInspection::class, 'vehicle_inspection_id')
      ->where('is_cancelled', false);
  }

  public function createdVehicleInspection(): HasOne
  {
    return $this->hasOne(ApVehicleInspection::class, 'ap_work_order_id');
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

  public function discountRequests()
  {
    return $this->hasMany(DiscountRequestsWorkOrder::class, 'ap_work_order_id');
  }

  public function internalNote(): HasOne
  {
    return $this->hasOne(ApInternalNote::class, 'work_order_id');
  }

  /**
   * Obtiene labours y parts dinámicamente:
   * - Si NO tiene cotización: retorna labours y parts existentes
   * - Si SÍ tiene cotización: retorna labours y parts existentes + items pendientes de la cotización
   */
  public function getDynamicItemsForInvoicing(?int $groupNumber = null): array
  {
    // Si NO tiene cotización asociada, retornar items existentes
    if (!$this->order_quotation_id || !$this->orderQuotation) {
      $labours = $groupNumber !== null
        ? $this->labours->where('group_number', $groupNumber)
        : $this->labours;

      $parts = $groupNumber !== null
        ? $this->parts->where('group_number', $groupNumber)
        : $this->parts;

      return [
        'labours' => $labours,
        'parts' => $parts,
      ];
    }

    // Si SÍ tiene cotización asociada, incluir items pendientes
    $labours = $groupNumber !== null
      ? $this->labours->where('group_number', $groupNumber)
      : $this->labours;

    $parts = $groupNumber !== null
      ? $this->parts->where('group_number', $groupNumber)
      : $this->parts;

    // Obtener items pendientes de la cotización
    $quotationLabours = collect();
    $quotationParts = collect();

    $pendingDetails = $this->orderQuotation->details
      ->where('status', ApOrderQuotationDetails::STATUS_PENDING);

    foreach ($pendingDetails as $detail) {
      if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
        // Mapear ApOrderQuotationDetails a estructura de WorkOrderLabour.
        // total_cost/net_amount/tax_amount se toman TAL CUAL del detalle (misma fuente
        // que usa calculateTotalsWithQuotation()), nunca se recalculan aquí: recalcular
        // asumiendo cantidad=1 producía un net_amount incorrecto cuando el detalle real
        // tenía otra cantidad (ej. 2.5 horas), y con eso este PDF llegó a mostrar un monto
        // muy por debajo del real.
        $mappedLabour = new \stdClass();
        $mappedLabour->id = null; // No tiene ID porque es de cotización
        $mappedLabour->description = $detail->description;
        $mappedLabour->time_spent = null;
        $mappedLabour->hourly_rate = (float)$detail->unit_price;
        $mappedLabour->discount_percentage = (float)$detail->discount_percentage;
        $mappedLabour->total_cost = (float)$detail->total_cost;
        $mappedLabour->tax_amount = (float)$detail->tax_amount;
        $mappedLabour->net_amount = (float)$detail->net_amount;
        $mappedLabour->worker_id = null;
        $mappedLabour->worker = null;
        $mappedLabour->group_number = null;
        $mappedLabour->work_order_id = $this->id;
        $mappedLabour->from_quotation = true; // Flag para identificar que viene de cotización

        $quotationLabours->push($mappedLabour);
      } elseif ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_PRODUCT) {
        // Mapear ApOrderQuotationDetails a estructura de ApWorkOrderParts.
        // Mismo criterio: total_cost/net_amount/tax_amount tal cual el detalle, sin
        // recalcular (antes tax_amount quedaba hardcodeado en 0 para estos ítems).
        $mappedPart = new \stdClass();
        $mappedPart->id = null; // No tiene ID porque es de cotización
        $mappedPart->product_id = $detail->product_id;
        $mappedPart->quantity_used = (float)$detail->quantity;
        $mappedPart->unit_cost = (float)($detail->purchase_price ?? 0);
        $mappedPart->unit_price = (float)$detail->unit_price;
        $mappedPart->discount_percentage = (float)$detail->discount_percentage;
        $mappedPart->total_cost = (float)$detail->total_cost;
        $mappedPart->tax_amount = (float)$detail->tax_amount;
        $mappedPart->net_amount = (float)$detail->net_amount;
        $mappedPart->product = $detail->product;
        $mappedPart->group_number = null;
        $mappedPart->work_order_id = $this->id;
        $mappedPart->warehouse_id = null;
        $mappedPart->warehouse = null;
        $mappedPart->registered_by = null;
        $mappedPart->is_delivered = false;
        $mappedPart->from_quotation = true; // Flag para identificar que viene de cotización

        $quotationParts->push($mappedPart);
      }
    }

    // Combinar items existentes con items pendientes de cotización
    $allLabours = $labours->concat($quotationLabours);
    $allParts = $parts->concat($quotationParts);

    return [
      'labours' => $allLabours,
      'parts' => $allParts,
    ];
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
   * Check if a document is accepted by SUNAT based on its type.
   * Boletas can be in 'sent' status (provider sometimes takes time to respond).
   * Facturas must be in 'accepted' status.
   *
   * @param ElectronicDocument $document
   * @return bool
   */
  private function isDocumentAcceptedBySunat($document): bool
  {
    // For boletas, accept if sent or accepted
    if ($document->sunat_concept_document_type_id === ElectronicDocument::TYPE_BOLETA) {
      return $document->status === ElectronicDocument::STATUS_SENT
        || $document->status === ElectronicDocument::STATUS_ACCEPTED;
    }

    // For facturas and other documents, must be accepted
    return $document->aceptada_por_sunat;
  }

  /**
   * Get active advances for this work order.
   *
   * An advance is truly cancelled (and therefore excluded) only when:
   *   - status = 'cancelled' (voided locally before SUNAT communication)
   *   - anulado = 1 (low-communication sent to SUNAT)
   *   - It has a linked credit note of type ANULACION or DEVOLUCION_TOTAL,
   *     which fully reverses the original transaction to zero.
   *
   * Advances with debit notes or partial credit notes (DESCUENTO_GLOBAL,
   * DEVOLUCION_ITEM, etc.) remain active — they only adjust the amount.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getActiveAdvances()
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    return $this->advancesWorkOrder->filter(function ($advance) use ($annullingTypes) {
      $passed = true;

      if (!$this->isDocumentAcceptedBySunat($advance)
        || !$advance->is_advance_payment
        || !in_array($advance->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        $passed = false;
      }

      if ($passed && ($advance->status === ElectronicDocument::STATUS_CANCELLED || $advance->anulado == 1)) {
        $passed = false;
      }

      if ($passed && $advance->credit_note_id !== null
        && in_array($advance->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        $passed = false;
      }

      return $passed;
    });
  }

  /**
   * Get cancelled advances for this work order.
   *
   * An advance is cancelled when:
   *   - status = 'cancelled', OR
   *   - anulado = 1, OR
   *   - It has a linked credit note of type ANULACION or DEVOLUCION_TOTAL.
   *
   * Advances with debit notes or partial credit notes are NOT cancelled.
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getCancelledAdvances()
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    return $this->advancesWorkOrder->filter(function ($advance) use ($annullingTypes) {
      if (!$this->isDocumentAcceptedBySunat($advance)
        || !$advance->is_advance_payment
        || !in_array($advance->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        return false;
      }

      if ($advance->status === ElectronicDocument::STATUS_CANCELLED || $advance->anulado == 1) {
        return true;
      }

      return $advance->credit_note_id !== null
        && in_array($advance->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes);
    });
  }

  /**
   * Get the final invoice (factura/boleta final) for this work order.
   *
   * A final invoice is:
   *   - NOT an advance payment (is_advance_payment = false)
   *   - Accepted by SUNAT
   *   - Type FACTURA or BOLETA
   *   - NOT cancelled (status != cancelled && anulado != 1)
   *   - NOT fully annulled by credit note
   *
   * @return ElectronicDocument|null
   */
  public function getFinalInvoice()
  {
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    return $this->advancesWorkOrder->first(function ($document) use ($annullingTypes) {
      // Must be final invoice (not advance)
      if ($document->is_advance_payment) {
        return false;
      }

      // Must be accepted by SUNAT
      if (!$this->isDocumentAcceptedBySunat($document)) {
        return false;
      }

      // Must be FACTURA or BOLETA
      if (!in_array($document->sunat_concept_document_type_id, [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])) {
        return false;
      }

      // Must not be cancelled
      if ($document->status === ElectronicDocument::STATUS_CANCELLED || $document->anulado == 1) {
        return false;
      }

      // Must not have annulling credit note
      if ($document->credit_note_id !== null
        && in_array($document->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        return false;
      }

      return true;
    });
  }

  /**
   * Get all valid documents for this work order (advances + final invoice).
   *
   * Returns a collection containing:
   *   - Active advances (from getActiveAdvances)
   *   - Final invoice if exists (from getFinalInvoice)
   *
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getValidDocuments()
  {
    $documents = collect();

    // Add active advances
    $activeAdvances = $this->getActiveAdvances();
    if ($activeAdvances->isNotEmpty()) {
      $documents = $documents->merge($activeAdvances);
    }

    // Add final invoice if exists
    $finalInvoice = $this->getFinalInvoice();
    if ($finalInvoice) {
      $documents->push($finalInvoice);
    }

    return $documents;
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
    $totalNet = 0;

    foreach ($this->getActiveAdvances() as $advance) {
      $totalNet += $this->getNetAmountForAdvance($advance);
    }

    return (float)$totalNet;
  }

  /**
   * Neto de un anticipo puntual (su total menos NC parciales, más ND) aplicando
   * la misma regla que getNetAmountFromAdvances(). Se usa también para armar la
   * línea "anticipo_regularizacion" en getInvoicePreview(), de modo que ambos
   * cuadren siempre entre sí.
   */
  private function getNetAmountForAdvance(ElectronicDocument $advance): float
  {
    $netAmount = $advance->total;

    // Restar notas de crédito sobre este anticipo (que NO sean de anulación/devolución total)
    // porque esas ya están excluidas por getActiveAdvances()
    $creditNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->whereNotIn('sunat_concept_credit_note_type_id', [
        SunatConcepts::ID_CREDIT_NOTE_ANULACION,
        SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
      ])
      ->get();

    foreach ($creditNotesOnAdvance as $creditNote) {
      $netAmount -= $creditNote->total;
    }

    // Sumar notas de débito sobre este anticipo
    $debitNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($debitNotesOnAdvance as $debitNote) {
      $netAmount += $debitNote->total;
    }

    return (float)$netAmount;
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
    $totalSubtotal = 0;
    $activeAdvances = $this->getActiveAdvances();

    foreach ($activeAdvances as $advance) {
      $advanceSubtotal = $this->getSubtotalForAdvance($advance);
      $totalSubtotal += $advanceSubtotal;
    }

    return (float)$totalSubtotal;
  }

  /**
   * Subtotal (sin IGV) de un anticipo puntual
   * Considera notas de crédito y débito sobre el anticipo
   *
   * @param ElectronicDocument $advance
   * @return float
   */
  private function getSubtotalForAdvance(ElectronicDocument $advance): float
  {
    $subtotal = $advance->total_gravada ?? 0;

    // Restar subtotales de notas de crédito sobre este anticipo
    $creditNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->whereNotIn('sunat_concept_credit_note_type_id', [
        SunatConcepts::ID_CREDIT_NOTE_ANULACION,
        SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
      ])
      ->get();

    foreach ($creditNotesOnAdvance as $creditNote) {
      $subtotal -= ($creditNote->total_gravada ?? 0);
    }

    // Sumar subtotales de notas de débito sobre este anticipo
    $debitNotesOnAdvance = ElectronicDocument::where('original_document_id', $advance->id)
      ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
      ->where('aceptada_por_sunat', true)
      ->where('anulado', 0)
      ->get();

    foreach ($debitNotesOnAdvance as $debitNote) {
      $subtotal += ($debitNote->total_gravada ?? 0);
    }

    return (float)$subtotal;
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
    $paidAmount = $this->getNetAmountFromAdvances();

    if ($paidAmount > 0 && $newFinalAmount < $paidAmount) {
      throw new \Exception(
        "El nuevo monto total (S/. " . number_format($newFinalAmount, 2) . ") " .
        "no puede ser menor al monto ya pagado en anticipos (S/. " . number_format($paidAmount, 2) . "). " .
        "Debe anular los anticipos correspondientes antes de reducir el monto de la orden de trabajo."
      );
    }
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
    // Obtener totales actuales
    $currentTotals = $this->getTotalsArray();

    // Variables para acumular el impacto del descuento
    $additionalDiscountAmount = 0;

    if ($type === DiscountRequestsWorkOrder::TYPE_PARTIAL) {
      // Descuento a un ítem específico
      if (!$partLabourId) {
        throw new \Exception('Para descuento PARTIAL se requiere el ID del ítem.');
      }

      // Obtener el ítem
      if ($partLabourModel === ApWorkOrderParts::class) {
        $item = $this->parts()->find($partLabourId);
        if (!$item) {
          throw new \Exception('Repuesto no encontrado.');
        }
      } elseif ($partLabourModel === WorkOrderLabour::class) {
        $item = $this->labours()->find($partLabourId);
        if (!$item) {
          throw new \Exception('Mano de obra no encontrada.');
        }
      } else {
        throw new \Exception('Tipo de ítem no válido.');
      }

      // Calcular el descuento adicional sobre este ítem
      $itemTotalCost = (float)$item->total_cost;
      $itemCurrentDiscount = $itemTotalCost - (float)$item->net_amount;

      // Nuevo descuento sobre el total_cost
      $newDiscountAmount = $itemTotalCost * ($discountPercentage / 100);

      // Descuento adicional = nuevo descuento - descuento actual
      $additionalDiscountAmount = $newDiscountAmount - $itemCurrentDiscount;

    } elseif ($type === DiscountRequestsWorkOrder::TYPE_GLOBAL) {
      // Descuento global a todos los ítems del tipo especificado
      if ($partLabourModel === ApWorkOrderParts::class) {
        $items = $this->parts;
      } elseif ($partLabourModel === WorkOrderLabour::class) {
        $items = $this->labours;
      } else {
        throw new \Exception('Tipo de ítem no válido.');
      }

      // Calcular el descuento adicional total
      foreach ($items as $item) {
        $itemTotalCost = (float)$item->total_cost;
        $itemCurrentDiscount = $itemTotalCost - (float)$item->net_amount;

        // Nuevo descuento sobre el total_cost
        $newDiscountAmount = $itemTotalCost * ($discountPercentage / 100);

        // Descuento adicional = nuevo descuento - descuento actual
        $additionalDiscountAmount += ($newDiscountAmount - $itemCurrentDiscount);
      }
    }

    // Calcular el nuevo net_amount (suma de todos los net_amount después del descuento)
    $currentNetAmount = (float)$currentTotals['net_amount'];
    $projectedNetAmount = $currentNetAmount - $additionalDiscountAmount;

    // Aplicar IGV sobre el nuevo net_amount
    $projectedTaxAmount = $projectedNetAmount * (Constants::VAT_TAX / 100);

    // Calcular el nuevo monto total final (incluye IGV)
    $projectedFinalAmount = $projectedNetAmount + $projectedTaxAmount;

    // Validar contra los anticipos pagados
    $this->validateMinimumAmount($projectedFinalAmount);
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
    $annullingTypes = [
      SunatConcepts::ID_CREDIT_NOTE_ANULACION,
      SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL,
    ];

    $cancelled = [];
    $active = [];

    // Process all documents
    foreach ($this->advancesWorkOrder as $document) {
      // Skip if not accepted by SUNAT or not the right type
      if (!$this->isDocumentAcceptedBySunat($document)
        || !in_array($document->sunat_concept_document_type_id, [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA
        ])) {
        continue;
      }

      $isCancelled = false;
      $cancellationReason = null;
      $creditNoteNumber = null;
      $creditNoteTypeId = null;
      $creditNoteTypeDescription = null;

      // Check if it's cancelled
      if ($document->status === ElectronicDocument::STATUS_CANCELLED || $document->anulado == 1) {
        $isCancelled = true;
        $cancellationReason = $document->observaciones;
      }

      // Check if it has an annulling credit note
      if ($document->credit_note_id !== null
        && in_array($document->creditNote?->sunat_concept_credit_note_type_id, $annullingTypes)) {
        $isCancelled = true;
        $cancellationReason = $document->creditNote?->observaciones;
        $creditNoteNumber = $document->creditNote?->full_number;
        $creditNoteTypeId = $document->creditNote?->sunat_concept_credit_note_type_id;
        $creditNoteTypeDescription = $document->creditNote?->creditNoteType?->description;
      }

      $documentData = [
        'id' => $document->id,
        'is_advance_payment' => (boolean)$document->is_advance_payment,
        'document_type' => $document->documentType->description,
        'number' => $document->full_number,
        'serie' => $document->serie,
        'numero' => $document->numero,
        'total' => (float)$document->total,
        'issue_date' => $document->fecha_de_emision?->format('Y-m-d'),
        'client_name' => $document->cliente_denominacion,
        'client_document' => $document->cliente_numero_de_documento,
        'status' => $document->status,
        'sunat_responsecode' => $document->sunat_responsecode,
        'enlace_del_pdf' => $document->enlace_del_pdf,
      ];

      if ($isCancelled) {
        $documentData['cancellation_reason'] = $cancellationReason;
        $documentData['credit_note_number'] = $creditNoteNumber;
        $documentData['sunat_concept_credit_note_type_id'] = $creditNoteTypeId;
        $documentData['credit_note_type_description'] = $creditNoteTypeDescription;
        $cancelled[] = $documentData;
      } else {
        // Get credit notes (excluding annulling types)
        $creditNotes = ElectronicDocument::where('original_document_id', $document->id)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
          ->where('aceptada_por_sunat', true)
          ->where('anulado', 0)
          ->whereNotIn('sunat_concept_credit_note_type_id', $annullingTypes)
          ->get();

        // Get debit notes
        $debitNotes = ElectronicDocument::where('original_document_id', $document->id)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
          ->where('aceptada_por_sunat', true)
          ->where('anulado', 0)
          ->get();

        $modifications = [];
        $netAmount = $document->total;

        // Add credit notes
        foreach ($creditNotes as $creditNote) {
          $modifications[] = [
            'id' => $creditNote->id,
            'type' => 'credit_note',
            'concept_type' => $creditNote->creditNoteType?->description,
            'concept_type_id' => $creditNote->sunat_concept_credit_note_type_id,
            'number' => $creditNote->full_number,
            'serie' => $creditNote->serie,
            'numero' => $creditNote->numero,
            'total' => -(float)$creditNote->total,
            'issue_date' => $creditNote->fecha_de_emision?->format('Y-m-d'),
            'original_document_id' => $document->id,
            'observaciones' => $creditNote->observaciones,
            'enlace_del_pdf' => $creditNote->enlace_del_pdf,
          ];
          $netAmount -= $creditNote->total;
        }

        // Add debit notes
        foreach ($debitNotes as $debitNote) {
          $modifications[] = [
            'id' => $debitNote->id,
            'type' => 'debit_note',
            'concept_type' => $debitNote->debitNoteType?->description,
            'concept_type_id' => $debitNote->sunat_concept_debit_note_type_id,
            'number' => $debitNote->full_number,
            'serie' => $debitNote->serie,
            'numero' => $debitNote->numero,
            'total' => (float)$debitNote->total,
            'issue_date' => $debitNote->fecha_de_emision?->format('Y-m-d'),
            'original_document_id' => $document->id,
            'observaciones' => $debitNote->observaciones,
            'enlace_del_pdf' => $debitNote->enlace_del_pdf,
          ];
          $netAmount += $debitNote->total;
        }

        $documentData['net_amount'] = (float)$netAmount;
        $documentData['has_modifications'] = count($modifications) > 0;
        $documentData['modifications'] = $modifications;

        $active[] = $documentData;
      }
    }

    return [
      'cancelled' => $cancelled,
      'active' => $active,
    ];
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
    $finalInvoice = $this->getFinalInvoice();
    $activeAdvances = $this->getActiveAdvances();

    // If there's a final invoice, total paid = sum of all active vouchers
    // Otherwise, only count advances with their credit/debit notes applied
    if ($finalInvoice) {
      $paidAmount = $activeAdvances->sum('total') + $finalInvoice->total;
    } else {
      $paidAmount = $this->getNetAmountFromAdvances();
    }

    $pendingAmount = max(0, $this->final_amount - $paidAmount);

    return [
      // Amount already paid/invoiced (advances + final invoice if exists)
      'paid_amount' => round((float)$paidAmount, 2),

      // Amount remaining to be paid/invoiced (same as remaining_balance for compatibility)
      'pending_amount' => round((float)$pendingAmount, 2),
      'remaining_balance' => round((float)$pendingAmount, 2),

      // Payment progress
      'payment_percentage' => $this->final_amount > 0
        ? round(($paidAmount / $this->final_amount) * 100, 2)
        : 0,

      // Payment status indicators
      'has_final_invoice' => $finalInvoice !== null,
      'advances_count' => $activeAdvances->count(),
    ];
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
    $items = $this->buildInvoiceItems();

    // La línea anticipo_regularizacion va en negativo (ver buildAdvanceInvoiceItem),
    // igual que buildRegularizationItems() para vehículos, así que SÍ se suma aquí junto
    // con el resto: el neto es justamente lo que falta facturar ahora (0 si el/los
    // anticipo(s) ya cubrieron todo). total_gravada/total_igv/total quedan siempre
    // = suma exacta de items_invoice, sin ninguna fórmula aparte.
    $totalGravada = 0;
    $totalIgv = 0;

    foreach ($items as $item) {
      $totalGravada += $item['subtotal'];
      $totalIgv += $item['igv'];
    }

    // total_anticipo es informativo (lo ya cobrado en anticipos), por eso se mantiene
    // positivo aunque su línea en items_invoice esté en negativo.
    // Usamos directamente los subtotales almacenados para evitar problemas de redondeo
    $totalAnticipo = $this->getSubtotalFromAdvances();

    // +0 normaliza el -0.0 que puede salir al cancelarse gravada/igv contra el anticipo
    // negativo (matemáticamente es cero, pero "-0" en el JSON se ve como un bug).
    return [
      'items_invoice' => $items,
      'invoice_preview' => [
        'total_gravada' => round($totalGravada, 2) + 0,
        'total_inafecta' => 0,
        'total_exonerada' => 0,
        'total_igv' => round($totalIgv, 2) + 0,
        'total_gratuita' => 0,
        'total_anticipo' => round($totalAnticipo, 2) + 0,
        'total' => round($totalGravada + $totalIgv, 2) + 0,
      ],
    ];
  }

  private function buildInvoiceItems(): array
  {
    $items = [];

    foreach ($this->labours as $labour) {
      $items[] = $this->buildLabourInvoiceItem($labour);
    }

    foreach ($this->parts as $part) {
      $items[] = $this->buildPartInvoiceItem($part);
    }

    // Misma condición que calculateTotalsWithQuotation(): si la OT está ligada a una
    // cotización, sus ítems PENDIENTES (aún no materializados en labours/parts) también
    // forman parte de lo que hay que facturar y ya cuentan en final_amount.
    if ($this->order_quotation_id && $this->orderQuotation) {
      $pendingDetails = $this->orderQuotation->details
        ->where('status', ApOrderQuotationDetails::STATUS_PENDING);

      foreach ($pendingDetails as $detail) {
        $items[] = $this->buildQuotationDetailInvoiceItem($detail);
      }
    }

    foreach ($this->getActiveAdvances() as $advance) {
      $items[] = $this->buildAdvanceInvoiceItem($advance);
    }

    return $items;
  }

  private function buildLabourInvoiceItem(WorkOrderLabour $labour): array
  {
    $isMaterial = trim(strtolower($labour->description ?? '')) === 'materiales';

    $billing = $this->calculateInvoiceItemAmounts(
      (float)$labour->hourly_rate,
      (float)$labour->time_spent_decimal,
      (float)$labour->discount_percentage,
      (float)$labour->net_amount,
      (float)$labour->tax_amount
    );

    return array_merge([
      'type' => 'labour',
      'source_id' => $labour->id,
      'account_plan_id' => $isMaterial
        ? ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID
        : ApAccountingAccountPlan::LABOUR_ACCOUNT_ID,
      'unidad_de_medida' => $this->getServiceUnitCode(),
      'codigo' => (string)$labour->id,
      'product_id' => null,
      'descripcion' => $labour->description,
      'cantidad' => (float)$labour->time_spent_decimal,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => false,
    ], $billing);
  }

  private function buildPartInvoiceItem(ApWorkOrderParts $part): array
  {
    $billing = $this->calculateInvoiceItemAmounts(
      (float)$part->unit_price,
      (float)$part->quantity_used,
      (float)$part->discount_percentage,
      (float)$part->net_amount,
      (float)$part->tax_amount
    );

    return array_merge([
      'type' => 'part',
      'source_id' => $part->id,
      'account_plan_id' => ApAccountingAccountPlan::AFTER_SALES_MAINTENANCE_SERVICE_ID,
      'unidad_de_medida' => $part->product?->unitMeasurement?->nubefac_code ?? 'NIU',
      'codigo' => $part->product?->code ?? (string)$part->product_id,
      'product_id' => $part->product_id,
      'descripcion' => $part->product?->name,
      'cantidad' => (float)$part->quantity_used,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => false,
    ], $billing);
  }

  private function buildQuotationDetailInvoiceItem(ApOrderQuotationDetails $detail): array
  {
    $billing = $this->calculateInvoiceItemAmounts(
      (float)$detail->unit_price,
      (float)$detail->quantity,
      (float)$detail->discount_percentage,
      (float)$detail->net_amount,
      (float)$detail->tax_amount
    );

    if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
      $isMaterial = trim(strtolower($detail->description ?? '')) === 'materiales';

      return array_merge([
        'type' => 'labour',
        'source_id' => $detail->id,
        'account_plan_id' => $isMaterial
          ? ApAccountingAccountPlan::LABOUR_ACCOUNT_MATERIAL_ID
          : ApAccountingAccountPlan::LABOUR_ACCOUNT_ID,
        'unidad_de_medida' => $this->getServiceUnitCode(),
        'codigo' => (string)$detail->id,
        'product_id' => null,
        'descripcion' => $detail->description,
        'cantidad' => (float)$detail->quantity,
        'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
        'anticipo_regularizacion' => false,
        'anticipo_documento_serie' => null,
        'anticipo_documento_numero' => null,
        'reference_document_id' => null,
        'from_quotation' => true,
      ], $billing);
    }

    return array_merge([
      'type' => 'part',
      'source_id' => $detail->id,
      'account_plan_id' => ApAccountingAccountPlan::AFTER_SALES_MAINTENANCE_SERVICE_ID,
      'unidad_de_medida' => $detail->product?->unitMeasurement?->nubefac_code ?? 'NIU',
      'codigo' => $detail->product?->code ?? (string)$detail->product_id,
      'product_id' => $detail->product_id,
      'descripcion' => $detail->product?->name ?? $detail->description,
      'cantidad' => (float)$detail->quantity,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_GRAVADO_ONEROSA,
      'anticipo_regularizacion' => false,
      'anticipo_documento_serie' => null,
      'anticipo_documento_numero' => null,
      'reference_document_id' => null,
      'from_quotation' => true,
    ], $billing);
  }

  private function buildAdvanceInvoiceItem(ElectronicDocument $advance): array
  {
    $netTotal = $this->getNetAmountForAdvance($advance);
    $valorUnitario = round($netTotal / (1 + Constants::VAT_TAX / 100), 2);
    $igv = round($netTotal - $valorUnitario, 2);

    return [
      'type' => 'anticipo_regularizacion',
      'source_id' => $advance->id,
      'account_plan_id' => ApAccountingAccountPlan::ADVANCE_PAYMENTS_ACCOUNT_ID,
      'unidad_de_medida' => $this->getServiceUnitCode(),
      'codigo' => (string)$advance->id,
      'product_id' => null,
      'descripcion' => 'ANTICIPO: ' . $advance->serie . '-' . $advance->numero
        . ' DEL ' . $advance->fecha_de_emision?->format('d/m/Y'),
      'cantidad' => 1,
      // Negativo, igual que buildRegularizationItems() para vehículos: esta línea resta
      // del total a facturar lo que ya se cobró como anticipo (no es solo informativa).
      'valor_unitario' => -$valorUnitario,
      'precio_unitario' => -round($netTotal, 2),
      'descuento' => null,
      'subtotal' => -$valorUnitario,
      'sunat_concept_igv_type_id' => SunatConcepts::ID_IGV_ANTICIPO_GRAVADO,
      'igv' => -$igv,
      'total' => -round($netTotal, 2),
      'anticipo_regularizacion' => true,
      'anticipo_documento_serie' => $advance->serie,
      'anticipo_documento_numero' => $advance->numero,
      'reference_document_id' => $advance->id,
      'from_quotation' => false,
    ];
  }

  /**
   * Código SUNAT (catálogo 03) de "servicio", única fuente de verdad para las líneas
   * de mano de obra y de anticipo en items_invoice. Cambiar el nubefac_code del
   * UnitMeasurement::SERVICE_ID en la BD basta para que ambas líneas se actualicen.
   */
  private function getServiceUnitCode(): string
  {
    return UnitMeasurement::find(UnitMeasurement::SERVICE_ID)?->nubefac_code ?? 'ZZ';
  }

  /**
   * valor_unitario/precio_unitario/descuento/subtotal/igv/total de una línea gravada.
   *
   * subtotal/igv se toman DIRECTO de net_amount/tax_amount ya persistidos (misma fuente
   * de verdad que WorkOrderLabourService/ApWorkOrderPartsService), en vez de recalcularlos
   * a partir de basePrice/quantity: recalcular redondeando el precio unitario antes de
   * multiplicarlo por una cantidad fraccionaria (ej. 7.5 litros) diverge unos centavos del
   * monto ya guardado. valor_unitario/precio_unitario/descuento se derivan de esos montos
   * ya redondeados solo para mostrar (nunca alimentan el total), para que con cantidad=1
   * precio_unitario coincida siempre con total: antes se recalculaba desde basePrice crudo
   * (sin descuento y con su propio redondeo), lo que lo desalineaba del total hasta en S/ 0.10.
   */
  private function calculateInvoiceItemAmounts(
    float $basePrice,
    float $quantity,
    float $discountPercentage,
    float $netAmount,
    float $taxAmount
  ): array
  {
    $subtotal = round($netAmount, 2);
    $igv = round($taxAmount, 2);
    $total = round($subtotal + $igv, 2);

    // Según SUNAT/UBL 2.1: valor_unitario y precio_unitario deben ser ANTES del descuento
    $valorUnitario = round($basePrice, 2);
    $precioUnitario = round($basePrice * (1 + Constants::VAT_TAX / 100), 2);
    $descuento = $discountPercentage > 0 ? round(($basePrice * $quantity) - $netAmount, 2) : null;

    return [
      'valor_unitario' => $valorUnitario,
      'precio_unitario' => $precioUnitario,
      'descuento' => $descuento,
      'subtotal' => $subtotal,
      'igv' => $igv,
      'total' => $total,
    ];
  }

  // Export Methods
  public static function getReportData($filters = [])
  {
    $query = self::with([
      'vehicle',
      'advisor',
      'sede',
      'status',
      'items.typePlanning',
      'creator',
      'typeCurrency',
      'invoiceTo'
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
      return [
        'id' => $workOrder->id,
        'correlativo' => $workOrder->correlative,
        'placa_vehiculo' => $workOrder->vehicle_plate,
        'vin_vehiculo' => $workOrder->vehicle_vin,
        'estado' => $workOrder->status ? $workOrder->status->description : '',
        'asesor' => $workOrder->advisor ? $workOrder->advisor->nombre_completo : '',
        'sede' => $workOrder->sede ? $workOrder->sede->abreviatura : '',
        'fecha_apertura' => $workOrder->opening_date ? $workOrder->opening_date->format('Y-m-d') : '',
        'fecha_entrega_estimada' => $workOrder->estimated_delivery_date ? $workOrder->estimated_delivery_date->format('Y-m-d H:i:s') : '',
        'fecha_entrega_real' => $workOrder->actual_delivery_date ? $workOrder->actual_delivery_date->format('Y-m-d H:i:s') : '',
        'fecha_diagnostico' => $workOrder->diagnosis_date ? $workOrder->diagnosis_date->format('Y-m-d H:i:s') : '',
        'moneda' => $workOrder->typeCurrency ? $workOrder->typeCurrency->symbol : '',
        'cliente_facturar' => $workOrder->invoiceTo ? $workOrder->invoiceTo->dyn_name : '',
        'subtotal' => number_format($workOrder->subtotal_amount ?? 0, 2),
        'descuento' => number_format($workOrder->discount_amount ?? 0, 2),
        'impuestos' => number_format($workOrder->tax_amount ?? 0, 2),
        'total' => number_format($workOrder->final_amount ?? 0, 2),
        'es_garantia' => $workOrder->is_guarantee ? 'Sí' : 'No',
        'es_recall' => $workOrder->is_recall ? 'Sí' : 'No',
        'esta_entregado' => $workOrder->is_delivery ? 'Sí' : 'No',
        'esta_facturado' => $workOrder->is_invoiced ? 'Sí' : 'No',
        'observaciones' => $workOrder->observations,
        'creado_por' => $workOrder->creator ? $workOrder->creator->name : '',
        'fecha_creacion' => $workOrder->created_at ? $workOrder->created_at->format('Y-m-d H:i:s') : '',
      ];
    });
  }

  public static function getReportableColumns()
  {
    return [
      'id' => 'ID',
      'correlativo' => 'Correlativo',
      'placa_vehiculo' => 'Placa Vehículo',
      'vin_vehiculo' => 'VIN Vehículo',
      'estado' => 'Estado',
      'asesor' => 'Asesor',
      'sede' => 'Sede',
      'fecha_apertura' => 'Fecha Apertura',
      'fecha_entrega_estimada' => 'Fecha Entrega Estimada',
      'fecha_entrega_real' => 'Fecha Entrega Real',
      'fecha_diagnostico' => 'Fecha Diagnóstico',
      'moneda' => 'Moneda',
      'cliente_facturar' => 'Cliente Facturar',
      'subtotal' => 'Subtotal',
      'descuento' => 'Descuento',
      'impuestos' => 'Impuestos',
      'total' => 'Total',
      'es_garantia' => 'Es Garantía',
      'es_recall' => 'Es Recall',
      'esta_entregado' => 'Está Entregado',
      'esta_facturado' => 'Está Facturado',
      'observaciones' => 'Observaciones',
      'creado_por' => 'Creado Por',
      'fecha_creacion' => 'Fecha Creación',
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
}
