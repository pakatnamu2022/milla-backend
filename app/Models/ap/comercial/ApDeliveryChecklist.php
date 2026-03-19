<?php

namespace App\Models\ap\comercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApDeliveryChecklist extends Model
{
  use SoftDeletes;

  protected $table = 'ap_delivery_checklist';

  protected $fillable = [
    'vehicle_delivery_id',
    'observations',
    'status',
    'confirmed_at',
    'confirmed_by',
    'created_by',
  ];

  protected $casts = [
    'confirmed_at' => 'datetime',
  ];

  const STATUS_DRAFT = 'draft';
  const STATUS_CONFIRMED = 'confirmed';

  public function vehicleDelivery(): BelongsTo
  {
    return $this->belongsTo(ApVehicleDelivery::class, 'vehicle_delivery_id');
  }

  public function items(): HasMany
  {
    return $this->hasMany(ApDeliveryChecklistItem::class, 'delivery_checklist_id')->orderBy('sort_order');
  }

  public function confirmedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'confirmed_by');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function isConfirmed(): bool
  {
    return $this->status === self::STATUS_CONFIRMED;
  }
}
