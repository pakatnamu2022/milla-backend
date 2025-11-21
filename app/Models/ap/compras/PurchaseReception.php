<?php

namespace App\Models\ap\compras;

use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseReception extends BaseModel
{
  use SoftDeletes;

  protected $table = 'purchase_receptions';

  protected $fillable = [
    'reception_number',
    'purchase_order_id',
    'reception_date',
    'warehouse_id',
    'shipping_guide_number',
    'status',
    'reception_type',
    'notes',
    'received_by',
    'reviewed_by',
    'reviewed_at',
    'total_items',
    'total_quantity',
  ];

  protected $casts = [
    'reception_date' => 'date',
    'reviewed_at' => 'datetime',
    'total_items' => 'integer',
    'total_quantity' => 'decimal:2',
  ];

  const filters = [
    'search' => ['reception_number', 'shipping_guide_number'],
    'purchase_order_id' => '=',
    'warehouse_id' => '=',
    'status' => '=',
    'reception_type' => '=',
    'reception_date' => '=',
    'received_by' => '=',
    'reviewed_by' => '=',
  ];

  const sorts = [
    'reception_number',
    'reception_date',
    'total_quantity',
    'created_at',
  ];

  // Mutators
  public function setReceptionNumberAttribute($value)
  {
    $this->attributes['reception_number'] = Str::upper(Str::ascii($value));
  }

  public function setShippingGuideNumberAttribute($value)
  {
    $this->attributes['shipping_guide_number'] = $value ? Str::upper(Str::ascii($value)) : null;
  }

  // Relationships
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function receivedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'received_by');
  }

  public function reviewedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }

  public function details(): HasMany
  {
    return $this->hasMany(PurchaseReceptionDetail::class, 'purchase_reception_id');
  }

  // Accessors
  public function getIsPendingReviewAttribute(): bool
  {
    return $this->status === 'PENDING_REVIEW';
  }

  public function getIsApprovedAttribute(): bool
  {
    return $this->status === 'APPROVED';
  }

  public function getIsRejectedAttribute(): bool
  {
    return $this->status === 'REJECTED';
  }

  public function getIsPartialAttribute(): bool
  {
    return $this->status === 'PARTIAL';
  }

  public function getHasBonusItemsAttribute(): bool
  {
    return $this->details()->where('reception_type', 'BONUS')->exists();
  }

  public function getHasGiftItemsAttribute(): bool
  {
    return $this->details()->where('reception_type', 'GIFT')->exists();
  }

  // Scopes
  public function scopePendingReview($query)
  {
    return $query->where('status', 'PENDING_REVIEW');
  }

  public function scopeApproved($query)
  {
    return $query->where('status', 'APPROVED');
  }

  public function scopeRejected($query)
  {
    return $query->where('status', 'REJECTED');
  }

  public function scopePartial($query)
  {
    return $query->where('status', 'PARTIAL');
  }

  public function scopeByPurchaseOrder($query, $purchaseOrderId)
  {
    return $query->where('purchase_order_id', $purchaseOrderId);
  }

  public function scopeByWarehouse($query, $warehouseId)
  {
    return $query->where('warehouse_id', $warehouseId);
  }

  public function scopeComplete($query)
  {
    return $query->where('reception_type', 'COMPLETE');
  }

  public function scopePartialReception($query)
  {
    return $query->where('reception_type', 'PARTIAL');
  }
}
