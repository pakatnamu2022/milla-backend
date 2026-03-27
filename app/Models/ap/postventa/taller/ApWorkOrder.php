<?php

namespace App\Models\ap\postventa\taller;

use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\gp\gestionhumana\personal\Worker;
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
    'allow_remove_associated_quote',
    'allow_editing_inspection',
    'created_by',
    'post_service_follow_up',
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
  ];

  const filters = [
    'search' => ['correlative', 'vehicle_plate', 'vehicle_vin', 'observations'],
    'correlative' => '=',
    'appointment_planning_id' => '=',
    'order_quotation_id' => '=',
    'vehicle_id' => '=',
    'vehicle_plate' => 'like',
    'vehicle_vin' => 'like',
    'status_id' => 'in_or_equal',
    'advisor_id' => '=',
    'sede_id' => '=',
    'opening_date' => 'date_between',
    'estimated_delivery_date' => 'between',
    'actual_delivery_date' => 'between',
    'diagnosis_date' => 'between',
    'is_invoiced' => '=',
    'created_by' => '=',
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
}
