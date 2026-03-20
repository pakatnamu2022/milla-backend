<?php

namespace App\Models\ap\postventa\taller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleInspection extends Model
{
  use softDeletes;

  protected $table = 'ap_vehicle_inspection';

  protected $fillable = [
    'ap_work_order_id',
    'inspection_date',
    'mileage',
    'fuel_level',
    'oil_level',
    'dirty_unit',
    'washed',
    'unit_ok',
    'title_deed',
    'soat',
    'moon_permits',
    'service_card',
    'owner_manual',
    'key_ring',
    'wheel_lock',
    'safe_glasses',
    'radio_mask',
    'lighter',
    'floors',
    'seat_cover',
    'quills',
    'antenna',
    'glasses_wheel',
    'emblems',
    'spare_tire',
    'fluid_caps',
    'tool_kit',
    'jack_and_lever',
    'general_observations',
    'customer_signature_url',
    'photo_front_url',
    'photo_back_url',
    'photo_left_url',
    'photo_right_url',
    'inspected_by',
    // Detalles de trabajo
    'oil_change',
    'check_level_lights',
    'general_lubrication',
    'rotation_inspection_cleaning',
    'insp_filter_basic_checks',
    'tire_pressure_inflation_check',
    'alignment_balancing',
    'pad_replace_disc_resurface',
    'other_work_details',
    // Requerimiento del cliente
    'customer_requirement',
    // Explicación de resultados
    'explanation_work_performed',
    'price_explanation',
    'confirm_additional_work',
    'clarification_customer_concerns',
    'exterior_cleaning',
    'interior_cleaning',
    'keeps_spare_parts',
    'valuable_objects',
    // Items de cortesía
    'courtesy_seat_cover',
    'paper_floor',
    //campos de cancelar
    'is_cancelled',
    'cancellation_requested_by',
    'cancellation_confirmed_by',
    'cancellation_requested_at',
    'cancellation_confirmed_at',
    'cancellation_reason',
  ];

  protected $casts = [
    'dirty_unit' => 'boolean',
    'washed' => 'boolean',
    'unit_ok' => 'boolean',
    'title_deed' => 'boolean',
    'soat' => 'boolean',
    'moon_permits' => 'boolean',
    'service_card' => 'boolean',
    'owner_manual' => 'boolean',
    'key_ring' => 'boolean',
    'wheel_lock' => 'boolean',
    'safe_glasses' => 'boolean',
    'radio_mask' => 'boolean',
    'lighter' => 'boolean',
    'floors' => 'boolean',
    'seat_cover' => 'boolean',
    'quills' => 'boolean',
    'antenna' => 'boolean',
    'glasses_wheel' => 'boolean',
    'emblems' => 'boolean',
    'spare_tire' => 'boolean',
    'fluid_caps' => 'boolean',
    'tool_kit' => 'boolean',
    'jack_and_lever' => 'boolean',
    'inspection_date' => 'datetime:H:i',
    // Detalles de trabajo
    'oil_change' => 'boolean',
    'check_level_lights' => 'boolean',
    'general_lubrication' => 'boolean',
    'rotation_inspection_cleaning' => 'boolean',
    'insp_filter_basic_checks' => 'boolean',
    'tire_pressure_inflation_check' => 'boolean',
    'alignment_balancing' => 'boolean',
    'pad_replace_disc_resurface' => 'boolean',
    // Explicación de resultados
    'confirm_additional_work' => 'boolean',
    'clarification_customer_concerns' => 'boolean',
    'exterior_cleaning' => 'boolean',
    'interior_cleaning' => 'boolean',
    'keeps_spare_parts' => 'boolean',
    'valuable_objects' => 'boolean',
    // Items de cortesía
    'courtesy_seat_cover' => 'boolean',
    'paper_floor' => 'boolean',
    // Campos de cancelación
    'is_cancelled' => 'boolean',
    'cancellation_requested_at' => 'datetime',
    'cancellation_confirmed_at' => 'datetime',
  ];

  const filters = [
    'search' => ['general_observations', 'createdByWorkOrder.vehicle.plate'],
    'fuel_level' => 'between',
    'inspected_by' => '=',
    'ap_work_order_id' => '=',
    'createdByWorkOrder.sede_id' => '=',
    'is_cancelled' => '=',
  ];

  const sorts = [
    'id',
    'fuel_level',
    'created_at',
  ];

  // Boot method
  protected static function boot()
  {
    parent::boot();

    // When deleting a reception, delete its items
    static::deleting(function ($reception) {
      $reception->damages()->delete();
    });
  }

  public function setGeneralObservationsAttribute($value)
  {
    $this->attributes['general_observations'] = strtoupper($value);
  }

  public function setCancellationReasonAttribute($value)
  {
    $this->attributes['cancellation_reason'] = strtoupper($value);
  }

  public function setOtherWorkDetailsAttribute($value)
  {
    $this->attributes['other_work_details'] = strtoupper($value);
  }

  public function setCustomerRequirementAttribute($value)
  {
    $this->attributes['customer_requirement'] = strtoupper($value);
  }

  public function damages()
  {
    return $this->hasMany(ApVehicleInspectionDamages::class, 'vehicle_inspection_id');
  }

  public function inspectionBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'inspected_by');
  }

  public function cancellationRequestedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'cancellation_requested_by');
  }

  public function cancellationConfirmedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'cancellation_confirmed_by');
  }

  public function createdByWorkOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'ap_work_order_id');
  }

  public function workOrders(): HasMany
  {
    return $this->hasMany(ApWorkOrder::class, 'vehicle_inspection_id');
  }
}
