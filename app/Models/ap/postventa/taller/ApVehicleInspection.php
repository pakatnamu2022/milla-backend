<?php

namespace App\Models\ap\postventa\taller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApVehicleInspection extends Model
{
  use softDeletes;

  protected $table = 'ap_vehicle_inspection';

  protected $fillable = [
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
    'signer_type',
    'photo_front_url',
    'photo_back_url',
    'photo_left_url',
    'photo_right_url',
    'photo_optional_1_url',
    'photo_optional_2_url',
    'photo_optional_3_url',
    'photo_optional_4_url',
    'photo_optional_5_url',
    'photo_optional_6_url',
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
    'tire_rotation',
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
    'tire_rotation' => 'boolean',
    // Explicación de resultados
    'explanation_work_performed' => 'boolean',
    'price_explanation' => 'boolean',
    'confirm_additional_work' => 'boolean',
    'clarification_customer_concerns' => 'boolean',
    'exterior_cleaning' => 'boolean',
    'interior_cleaning' => 'boolean',
    'keeps_spare_parts' => 'boolean',
    'valuable_objects' => 'boolean',
    // Items de cortesía
    'courtesy_seat_cover' => 'boolean',
    'paper_floor' => 'boolean',
  ];

  const filters = [
    'search' => ['general_observations', 'createdByWorkOrder.vehicle.plate'],
    'fuel_level' => 'between',
    'inspected_by' => '=',
    'inspection_date' => 'date_between',
    'is_cancelled' => 'scope',
    'createdByWorkOrder.sede_id' => '=',
    'createdByWorkOrder.vehicle_id' => '=',
    'createdByWorkOrder.is_delivery' => '=',
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

  /**
   * Obtiene todos los registros pivot de órdenes que usan esta inspección
   */
  public function workOrderPivots(): HasMany
  {
    return $this->hasMany(WorkOrderVehicleInspection::class, 'vehicle_inspection_id');
  }

  /**
   * Obtiene solo los pivots NO anulados
   */
  public function activeWorkOrderPivots(): HasMany
  {
    return $this->hasMany(WorkOrderVehicleInspection::class, 'vehicle_inspection_id')
      ->where('is_cancelled', false)
      ->latest('id');
  }

  /**
   * Relación para obtener la orden de trabajo activa (no cancelada)
   * Usa hasOneThrough para soportar eager loading y filtros
   */
  public function createdByWorkOrder(): HasOneThrough
  {
    return $this->hasOneThrough(
      ApWorkOrder::class,
      WorkOrderVehicleInspection::class,
      'vehicle_inspection_id', // Foreign key en work_order_vehicle_inspection
      'id',                     // Foreign key en ap_work_orders
      'id',                     // Local key en ap_vehicle_inspection
      'work_order_id'           // Local key en work_order_vehicle_inspection
    )->where('work_order_vehicle_inspection.is_cancelled', false)
      ->latest('work_order_vehicle_inspection.id');
  }

  /**
   * Obtiene la orden de trabajo activa (no cancelada) asociada a esta inspección
   * Normalmente debería haber solo una activa
   * @return ApWorkOrder|null
   * @deprecated Usar la relación createdByWorkOrder() directamente
   */
  public function getActiveWorkOrder(): ?ApWorkOrder
  {
    return $this->createdByWorkOrder;
  }

  /**
   * Scope para filtrar inspecciones por estado de cancelación
   * Filtra según el campo is_cancelled en la tabla pivot
   */
  public function scopeIsCancelled($query, $value)
  {
    $isCancelled = filter_var($value, FILTER_VALIDATE_BOOLEAN);

    return $query->whereHas('workOrderPivots', function ($q) use ($isCancelled) {
      $q->where('is_cancelled', $isCancelled);
    });
  }
}
