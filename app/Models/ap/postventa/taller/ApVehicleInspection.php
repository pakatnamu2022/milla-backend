<?php

namespace App\Models\ap\postventa\taller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    'inspection_date' => 'datetime',
  ];

  const filters = [
    'search' => ['general_observations', 'workOrder.vehicle.plate'],
    'fuel_level' => 'between',
    'inspected_by' => '=',
    'ap_work_order_id' => '=',
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

  public function workOrder(): HasOne
  {
    return $this->hasOne(ApWorkOrder::class, 'id', 'ap_work_order_id');
  }
}
