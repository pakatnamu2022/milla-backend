<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApWorkOrder extends Model
{
  use SoftDeletes;

  protected $table = 'ap_work_orders';

  protected $fillable = [
    'correlative',
    'appointment_planning_id',
    'vehicle_inspection_id',
    'order_quotation_id',
    'vehicle_id',
    'currency_id',
    'vehicle_plate',
    'vehicle_vin',
    'status_id',
    'advisor_id',
    'sede_id',
    'opening_date',
    'estimated_delivery_date',
    'actual_delivery_date',
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
    'created_by',
  ];

  protected $casts = [
    'opening_date' => 'date',
    'estimated_delivery_date' => 'date',
    'actual_delivery_date' => 'datetime',
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
  ];

  const filters = [
    'search' => ['correlative', 'vehicle_plate', 'vehicle_vin', 'observations'],
    'correlative' => '=',
    'appointment_planning_id' => '=',
    'vehicle_inspection_id' => '=',
    'order_quotation_id' => '=',
    'vehicle_id' => '=',
    'vehicle_plate' => 'like',
    'vehicle_vin' => 'like',
    'status_id' => '=',
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
    return $this->belongsTo(ApVehicleInspection::class, 'vehicle_inspection_id');
  }

  // Helper methods
  public function calculateTotals(): void
  {
    $this->subtotal = $this->total_labor_cost + $this->total_parts_cost;
    $this->final_amount = $this->subtotal - $this->discount_amount + $this->tax_amount;
    $this->save();
  }

  public function advancesWorkOrder(): HasMany
  {
    return $this->hasMany(ElectronicDocument::class, 'work_order_id')
      ->where('is_advance_payment', true);
  }
}
