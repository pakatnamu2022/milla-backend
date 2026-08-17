<?php

namespace App\Models\ap\postventa\taller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderVehicleInspection extends Model
{
  use SoftDeletes;

  protected $table = 'work_order_vehicle_inspection';

  protected $fillable = [
    'work_order_id',
    'vehicle_inspection_id',
    'is_cancelled',
    'cancellation_requested_by',
    'cancellation_confirmed_by',
    'cancellation_requested_at',
    'cancellation_confirmed_at',
    'cancellation_reason',
  ];

  protected $casts = [
    'is_cancelled' => 'boolean',
    'cancellation_requested_at' => 'datetime',
    'cancellation_confirmed_at' => 'datetime',
  ];

  const filters = [
    'work_order_id' => '=',
    'vehicle_inspection_id' => '=',
    'is_cancelled' => '=',
  ];

  const sorts = [
    'id',
    'created_at',
  ];

  // Mutators
  public function setCancellationReasonAttribute($value)
  {
    $this->attributes['cancellation_reason'] = strtoupper($value);
  }

  // Relations
  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  public function vehicleInspection(): BelongsTo
  {
    return $this->belongsTo(ApVehicleInspection::class, 'vehicle_inspection_id');
  }

  public function cancellationRequestedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'cancellation_requested_by');
  }

  public function cancellationConfirmedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'cancellation_confirmed_by');
  }
}