<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\maestroGeneral\SunatConcepts;
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

    // Actualizar los campos de la orden de trabajo
    $this->total_labor_cost = $totals['labour_cost'];
    $this->total_parts_cost = $totals['parts_cost'];
    $this->subtotal = $totals['total_cost'];
    $this->discount_amount = $totals['discount_amount'];
    $this->tax_amount = $totals['tax_amount'];
    $this->final_amount = $totals['total_amount'];

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

    // Subtotal sin descuentos
    $subtotal = $totalLabourCostBeforeDiscount + $totalPartsCostBeforeDiscount;

    // Total de descuentos de items (la diferencia entre total_cost y net_amount)
    $itemsDiscountAmount = ($totalLabourCostBeforeDiscount - $totalLabourNetAmount) + ($totalPartsCostBeforeDiscount - $totalPartsNetAmount);

    // Net amount (suma de net_amount de items)
    $netAmount = $totalLabourNetAmount + $totalPartsNetAmount;

    // IGV sobre el net amount
    $taxAmount = $netAmount * (Constants::VAT_TAX / 100);

    // Total final
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

    // Calculate costs from existing labours (sin descuento)
    $totalLabourCostBeforeDiscount = 0;
    $totalLabourDiscount = 0;
    foreach ($labours as $labour) {
      $cost = $labour->total_cost ?? 0;
      $discount = ($cost * ($labour->discount_percentage ?? 0)) / 100;
      $totalLabourCostBeforeDiscount += $cost;
      $totalLabourDiscount += $discount;
    }

    // Calculate costs from existing parts (sin descuento)
    $totalPartsCostBeforeDiscount = 0;
    $totalPartsDiscount = 0;
    foreach ($parts as $part) {
      $cost = $part->total_cost ?? 0;
      $netAmount = $part->net_amount ?? 0;
      $discount = $cost - $netAmount;
      $totalPartsCostBeforeDiscount += $cost;
      $totalPartsDiscount += $discount;
    }

    // Add pending quotation details (solo si no se filtró por group_number, ya que la cotización no tiene group_number)
    if ($groupNumber === null && $this->orderQuotation && $this->orderQuotation->details) {
      $pendingDetails = $this->orderQuotation->details
        ->where('status', ApOrderQuotationDetails::STATUS_PENDING);

      foreach ($pendingDetails as $detail) {
        $quantity = $detail->quantity ?? 0;
        $unitPrice = $detail->unit_price ?? 0;
        $discountPercentage = $detail->discount_percentage ?? 0;

        $itemSubtotal = $quantity * $unitPrice;
        $itemDiscount = ($itemSubtotal * $discountPercentage) / 100;

        if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR) {
          $totalLabourCostBeforeDiscount += $itemSubtotal;
          $totalLabourDiscount += $itemDiscount;
        } elseif ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_PRODUCT) {
          $totalPartsCostBeforeDiscount += $itemSubtotal;
          $totalPartsDiscount += $itemDiscount;
        }
      }
    }

    // Calculate totals
    // Subtotal sin descuento
    $subtotal = $totalLabourCostBeforeDiscount + $totalPartsCostBeforeDiscount;

    // Total de descuentos de items
    $itemsDiscountAmount = $totalLabourDiscount + $totalPartsDiscount;

    // Net amount (suma de net_amount de items)
    $netAmountLabour = $totalLabourCostBeforeDiscount - $totalLabourDiscount;
    $netAmountParts = $totalPartsCostBeforeDiscount - $totalPartsDiscount;
    $netAmount = $netAmountLabour + $netAmountParts;

    // IGV sobre el net amount
    $taxAmount = $netAmount * (Constants::VAT_TAX / 100);

    // Total Final
    $totalAmount = $netAmount + $taxAmount;

    return [
      'labour_cost' => (float)$totalLabourCostBeforeDiscount,
      'parts_cost' => (float)$totalPartsCostBeforeDiscount,
      'labour_cost_desc' => (float)$netAmountLabour,
      'parts_cost_desc' => (float)$netAmountParts,
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
        // Mapear ApOrderQuotationDetails a estructura de WorkOrderLabour
        $quantity = 1; // Para labores, la cantidad es 1
        $unitPrice = $detail->unit_price ?? 0;
        $discountPercentage = $detail->discount_percentage ?? 0;
        $subtotal = $quantity * $unitPrice;
        $discountAmount = ($subtotal * $discountPercentage) / 100;
        $total = $subtotal - $discountAmount;

        $mappedLabour = new \stdClass();
        $mappedLabour->id = null; // No tiene ID porque es de cotización
        $mappedLabour->description = $detail->description;
        $mappedLabour->time_spent = null;
        $mappedLabour->hourly_rate = $unitPrice;
        $mappedLabour->discount_percentage = $discountPercentage;
        $mappedLabour->total_cost = $subtotal; // Total sin descuento
        $mappedLabour->net_amount = $total; // Total con descuento aplicado
        $mappedLabour->worker_id = null;
        $mappedLabour->worker = null;
        $mappedLabour->group_number = null;
        $mappedLabour->work_order_id = $this->id;
        $mappedLabour->from_quotation = true; // Flag para identificar que viene de cotización

        $quotationLabours->push($mappedLabour);
      } elseif ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_PRODUCT) {
        // Mapear ApOrderQuotationDetails a estructura de ApWorkOrderParts
        $quantity = $detail->quantity ?? 0;
        $unitPrice = $detail->unit_price ?? 0;
        $discountPercentage = $detail->discount_percentage ?? 0;
        $subtotal = $quantity * $unitPrice;
        $discountAmount = ($subtotal * $discountPercentage) / 100;
        $total = $subtotal - $discountAmount;

        $mappedPart = new \stdClass();
        $mappedPart->id = null; // No tiene ID porque es de cotización
        $mappedPart->product_id = $detail->product_id;
        $mappedPart->quantity_used = $quantity;
        $mappedPart->unit_cost = $detail->purchase_price ?? 0;
        $mappedPart->unit_price = $unitPrice;
        $mappedPart->discount_percentage = $discountPercentage;
        $mappedPart->total_cost = $subtotal; // Total sin descuento
        $mappedPart->tax_amount = 0;
        $mappedPart->net_amount = $total; // Total con descuento aplicado
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

      if (!$advance->aceptada_por_sunat
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
      if (!$advance->aceptada_por_sunat
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
      if (!$document->aceptada_por_sunat) {
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
    $activeAdvances = $this->getActiveAdvances();

    $totalNet = 0;

    foreach ($activeAdvances as $advance) {
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

      $totalNet += $netAmount;
    }

    return (float)$totalNet;
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
    float $discountPercentage,
    ?int $partLabourId = null
  ): void {
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
      if (!$document->aceptada_por_sunat
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

    // Account for rounding tolerance (same as ElectronicDocument::ROUNDING_TOLERANCE)
    // This allows for small differences caused by cumulative rounding in IGV calculations
    $isFullyPaid = $pendingAmount <= ElectronicDocument::ROUNDING_TOLERANCE;

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
      'is_fully_paid' => $isFullyPaid,
      'has_final_invoice' => $finalInvoice !== null,
      'advances_count' => $activeAdvances->count(),
    ];
  }
}
