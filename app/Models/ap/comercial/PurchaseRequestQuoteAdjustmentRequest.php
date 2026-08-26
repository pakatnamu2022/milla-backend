<?php

namespace App\Models\ap\comercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequestQuoteAdjustmentRequest extends Model
{
  use SoftDeletes;

  protected $table = 'purchase_request_quote_adjustment_requests';

  protected $fillable = [
    'purchase_request_quote_id',
    'requested_by_id',
    'status',
    'reason',
    'margin_amount_before',
    'margin_pct_before',
    'margin_amount_after',
    'margin_pct_after',
    'resolved_by_id',
    'resolved_at',
    'rejection_reason',
  ];

  protected $casts = [
    'resolved_at' => 'datetime',
    'margin_amount_before' => 'float',
    'margin_pct_before' => 'float',
    'margin_amount_after' => 'float',
    'margin_pct_after' => 'float',
  ];

  const filters = [
    'purchase_request_quote_id' => '=',
    'requested_by_id' => '=',
    'status' => 'in',
  ];

  const sorts = [
    'id',
    'created_at',
    'resolved_at',
  ];

  const STATUS_PENDING = 'pending';
  const STATUS_APPROVED = 'approved';
  const STATUS_REJECTED = 'rejected';

  public function scopePending(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  public function scopeApproved(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_APPROVED);
  }

  public function scopeRejected(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_REJECTED);
  }

  public function purchaseRequestQuote(): BelongsTo
  {
    return $this->belongsTo(PurchaseRequestQuote::class, 'purchase_request_quote_id');
  }

  public function requestedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'requested_by_id');
  }

  public function resolvedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'resolved_by_id');
  }

  public function items(): HasMany
  {
    return $this->hasMany(PurchaseRequestQuoteAdjustmentItem::class, 'adjustment_request_id');
  }
}
