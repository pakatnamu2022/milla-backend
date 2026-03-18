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
    'opening_date',
    'estimated_delivery_date',
    'estimated_delivery_time',
    'actual_delivery_date',
    'actual_delivery_time',
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
  ];

  protected $casts = [
    'opening_date' => 'date',
    'estimated_delivery_date' => 'datetime',
    'estimated_delivery_time' => 'datetime:H:i',
    'actual_delivery_date' => 'datetime',
    'actual_delivery_time' => 'datetime:H:i',
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
    // Si tiene cotización asociada, usar el cálculo que incluye items pendientes de la cotización
    if ($this->order_quotation_id && $this->orderQuotation) {
      $totals = $this->calculateTotalsWithQuotation();
    } else {
      // Cálculo tradicional sin cotización
      $totals = $this->calculateTotalsWithoutQuotation();
    }

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
   * Calcula totales SIN considerar cotización (solo labours y parts existentes)
   */
  private function calculateTotalsWithoutQuotation(): array
  {
    // Calculate costs (sin descuento de items)
    $totalLabourCostBeforeDiscount = $this->labours()->sum('total_cost') ?? 0;
    $totalPartsCostBeforeDiscount = $this->parts()->sum('total_cost') ?? 0;

    // Calculate net amounts (con descuento de items aplicado)
    $totalLabourNetAmount = $this->labours()->sum('net_amount') ?? 0;
    $totalPartsNetAmount = $this->parts()->sum('net_amount') ?? 0;

    // Subtotal sin descuentos
    $subtotal = $totalLabourCostBeforeDiscount + $totalPartsCostBeforeDiscount;

    // Total de descuentos de items
    $itemsDiscountAmount = ($totalLabourCostBeforeDiscount - $totalLabourNetAmount) + ($totalPartsCostBeforeDiscount - $totalPartsNetAmount);

    // Descuento general de la orden de trabajo
    $workOrderDiscountAmount = $this->discount_amount ?? 0;

    // Total de descuentos
    $totalDiscountAmount = $itemsDiscountAmount + $workOrderDiscountAmount;

    // Net amount (suma de net_amount de items)
    $netAmount = $totalLabourNetAmount + $totalPartsNetAmount;

    // Net amount final (restar descuento general de OT)
    $netAmountFinal = $netAmount - $workOrderDiscountAmount;

    // IGV sobre el net amount final
    $taxAmount = $netAmountFinal * (Constants::VAT_TAX / 100);

    // Total final
    $totalAmount = $netAmountFinal + $taxAmount;

    return [
      'labour_cost' => (float)$totalLabourCostBeforeDiscount,
      'parts_cost' => (float)$totalPartsCostBeforeDiscount,
      'labour_cost_desc' => (float)$totalLabourNetAmount,
      'parts_cost_desc' => (float)$totalPartsNetAmount,
      'total_cost' => (float)$subtotal,
      'net_amount' => (float)$netAmount,
      'discount_amount' => (float)$totalDiscountAmount,
      'tax_amount' => (float)$taxAmount,
      'total_amount' => (float)$totalAmount,
    ];
  }

  /**
   * Calcula totales INCLUYENDO items pendientes de la cotización
   */
  private function calculateTotalsWithQuotation(): array
  {
    // Calculate costs from existing labours (sin descuento)
    $totalLabourCostBeforeDiscount = 0;
    $totalLabourDiscount = 0;
    foreach ($this->labours as $labour) {
      $cost = $labour->total_cost ?? 0;
      $discount = ($cost * ($labour->discount_percentage ?? 0)) / 100;
      $totalLabourCostBeforeDiscount += $cost;
      $totalLabourDiscount += $discount;
    }

    // Calculate costs from existing parts (sin descuento)
    $totalPartsCostBeforeDiscount = 0;
    $totalPartsDiscount = 0;
    foreach ($this->parts as $part) {
      $cost = $part->total_cost ?? 0;
      $netAmount = $part->net_amount ?? 0;
      $discount = $cost - $netAmount;
      $totalPartsCostBeforeDiscount += $cost;
      $totalPartsDiscount += $discount;
    }

    // Add pending quotation details
    if ($this->orderQuotation && $this->orderQuotation->details) {
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

    // Total de descuentos (de items + descuento general de la OT)
    $itemsDiscountAmount = $totalLabourDiscount + $totalPartsDiscount;
    $workOrderDiscountAmount = $this->discount_amount ?? 0;
    $totalDiscountAmount = $itemsDiscountAmount + $workOrderDiscountAmount;

    // Net amount (suma de net_amount de items)
    $netAmountLabour = $totalLabourCostBeforeDiscount - $totalLabourDiscount;
    $netAmountParts = $totalPartsCostBeforeDiscount - $totalPartsDiscount;
    $netAmount = $netAmountLabour + $netAmountParts;

    // Net amount final (restar descuento general de OT)
    $netAmountFinal = $netAmount - $workOrderDiscountAmount;

    // IGV sobre el net amount final
    $taxAmount = $netAmountFinal * (Constants::VAT_TAX / 100);

    // Total Final
    $totalAmount = $netAmountFinal + $taxAmount;

    return [
      'labour_cost' => (float)$totalLabourCostBeforeDiscount,
      'parts_cost' => (float)$totalPartsCostBeforeDiscount,
      'labour_cost_desc' => (float)$netAmountLabour,
      'parts_cost_desc' => (float)$netAmountParts,
      'total_cost' => (float)$subtotal,
      'net_amount' => (float)$netAmount,
      'discount_amount' => (float)$totalDiscountAmount,
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
}
