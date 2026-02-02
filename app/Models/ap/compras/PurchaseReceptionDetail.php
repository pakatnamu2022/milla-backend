<?php

namespace App\Models\ap\compras;

use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseReceptionDetail extends Model
{
  use SoftDeletes;

  protected $table = 'purchase_reception_details';

  protected $fillable = [
    'purchase_reception_id',
    'purchase_order_item_id',
    'product_id',
    'quantity_received',
    'observed_quantity',
    'reception_type',
    'reason_observation',
    'observation_notes',
    'bonus_reason',
    'batch_number',
    'expiration_date',
    'notes',
  ];

  protected $casts = [
    'quantity_received' => 'decimal:2',
    'observed_quantity' => 'decimal:2',
    'expiration_date' => 'date',
  ];

  // Reception_type

  const RECEPTION_TYPE_ORDERED = 'ORDERED';
  const RECEPTION_TYPE_BONUS = 'BONUS';
  const RECEPTION_TYPE_GIFT = 'GIFT';
  const RECEPTION_TYPE_SAMPLE = 'SAMPLE';

  // Reason_observation

  const REASON_DAMAGED = 'DAMAGED';
  const REASON_DEFECTIVE = 'DEFECTIVE';
  const REASON_EXPIRED = 'EXPIRED';
  const REASON_WRONG_PRODUCT = 'WRONG_PRODUCT';
  const REASON_WRONG_QUANTITY = 'WRONG_QUANTITY';
  const REASON_POOR_QUALITY = 'POOR_QUALITY';
  const REASON_OTHER = 'OTHER';

  public function setObservationNotesAttribute($value)
  {
    $this->attributes['observation_notes'] = Str::upper($value);
  }

  public static function getReceptionTypeLabel($type): string
  {
    return match ($type) {
      self::RECEPTION_TYPE_ORDERED => 'Orden',
      self::RECEPTION_TYPE_BONUS => 'Bonus',
      self::RECEPTION_TYPE_GIFT => 'Regalo',
      self::RECEPTION_TYPE_SAMPLE => 'Muestra',
    };
  }

  public static function getReasonObservationLabel($reason): string
  {
    return match ($reason) {
      self::REASON_DAMAGED => 'Damaged',
      self::REASON_DEFECTIVE => 'Defective',
      self::REASON_EXPIRED => 'Expired',
      self::REASON_WRONG_PRODUCT => 'Wrong Product',
      self::REASON_WRONG_QUANTITY => 'Wrong Quantity',
      self::REASON_POOR_QUALITY => 'Poor Quality',
      self::REASON_OTHER => 'Other',
    };
  }

  // Relationships
  public function reception(): BelongsTo
  {
    return $this->belongsTo(PurchaseReception::class, 'purchase_reception_id');
  }

  public function purchaseOrderItem(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  // Accessors
  public function getIsOrderedAttribute(): bool
  {
    return $this->reception_type === 'ORDERED';
  }

  public function getIsBonusAttribute(): bool
  {
    return $this->reception_type === 'BONUS';
  }

  public function getIsGiftAttribute(): bool
  {
    return $this->reception_type === 'GIFT';
  }

  public function getIsSampleAttribute(): bool
  {
    return $this->reception_type === 'SAMPLE';
  }

  public function getHasObservedQuantityAttribute(): bool
  {
    return $this->observed_quantity > 0;
  }

  // Scopes
  public function scopeOrdered($query)
  {
    return $query->where('reception_type', 'ORDERED');
  }

  public function scopeBonus($query)
  {
    return $query->where('reception_type', 'BONUS');
  }

  public function scopeGift($query)
  {
    return $query->where('reception_type', 'GIFT');
  }

  public function scopeSample($query)
  {
    return $query->where('reception_type', 'SAMPLE');
  }

  public function scopeWithObservations($query)
  {
    return $query->where('observed_quantity', '>', 0);
  }

  public function scopeByProduct($query, $productId)
  {
    return $query->where('product_id', $productId);
  }
}
