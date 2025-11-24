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

  public function setObservationNotesAttribute($value)
  {
    $this->attributes['observation_notes'] = Str::upper($value);
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
