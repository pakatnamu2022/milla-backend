<?php

namespace App\Models\ap\compras;

use App\Models\ap\comercial\BusinessPartners;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupplierCreditNote extends Model
{
  use SoftDeletes;

  protected $table = 'supplier_credit_notes';

  protected $fillable = [
    'credit_note_number',
    'purchase_order_id',
    'purchase_reception_id',
    'supplier_id',
    'credit_note_date',
    'reason',
    'subtotal',
    'tax_amount',
    'total',
    'status',
    'notes',
    'approved_by',
    'approved_at',
  ];

  protected $casts = [
    'credit_note_date' => 'date',
    'subtotal' => 'decimal:2',
    'tax_amount' => 'decimal:2',
    'total' => 'decimal:2',
    'approved_at' => 'datetime',
  ];

  const filters = [
    'search' => ['credit_note_number'],
    'purchase_order_id' => '=',
    'purchase_reception_id' => '=',
    'supplier_id' => '=',
    'reason' => '=',
    'status' => '=',
    'credit_note_date' => '=',
  ];

  const sorts = [
    'credit_note_number',
    'credit_note_date',
    'total',
    'created_at',
  ];

  // Reasons
  const REASON_SHORTAGE = 'SHORTAGE';
  const REASON_RETURN = 'RETURN';
  const REASON_DISCOUNT = 'DISCOUNT';
  const REASON_BILLING_ERROR = 'BILLING_ERROR';
  const REASON_DAMAGED_GOODS = 'DAMAGED_GOODS';
  const REASON_PRICE_ADJUSTMENT = 'PRICE_ADJUSTMENT';

  // Status
  const STATUS_DRAFT = 'DRAFT';
  const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
  const STATUS_APPROVED = 'APPROVED';
  const STATUS_APPLIED = 'APPLIED';
  const STATUS_REJECTED = 'REJECTED';
  const STATUS_CANCELLED = 'CANCELLED';

  // Mutators
  public function setCreditNoteNumberAttribute($value)
  {
    $this->attributes['credit_note_number'] = Str::upper(Str::ascii($value));
  }

  // Relationships
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
  }

  public function purchaseReception(): BelongsTo
  {
    return $this->belongsTo(PurchaseReception::class, 'purchase_reception_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'supplier_id');
  }

  public function approvedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approved_by');
  }

  public function details(): HasMany
  {
    return $this->hasMany(SupplierCreditNoteDetail::class, 'supplier_credit_note_id');
  }

  // Accessors
  public function getIsDraftAttribute(): bool
  {
    return $this->status === self::STATUS_DRAFT;
  }

  public function getIsPendingApprovalAttribute(): bool
  {
    return $this->status === self::STATUS_PENDING_APPROVAL;
  }

  public function getIsApprovedAttribute(): bool
  {
    return $this->status === self::STATUS_APPROVED;
  }

  public function getIsAppliedAttribute(): bool
  {
    return $this->status === self::STATUS_APPLIED;
  }

  public function getIsRejectedAttribute(): bool
  {
    return $this->status === self::STATUS_REJECTED;
  }

  public function getIsCancelledAttribute(): bool
  {
    return $this->status === self::STATUS_CANCELLED;
  }

  // Scopes
  public function scopeDraft($query)
  {
    return $query->where('status', self::STATUS_DRAFT);
  }

  public function scopePendingApproval($query)
  {
    return $query->where('status', self::STATUS_PENDING_APPROVAL);
  }

  public function scopeApproved($query)
  {
    return $query->where('status', self::STATUS_APPROVED);
  }

  public function scopeApplied($query)
  {
    return $query->where('status', self::STATUS_APPLIED);
  }

  public function scopeRejected($query)
  {
    return $query->where('status', self::STATUS_REJECTED);
  }

  public function scopeBySupplier($query, $supplierId)
  {
    return $query->where('supplier_id', $supplierId);
  }

  public function scopeByPurchaseOrder($query, $purchaseOrderId)
  {
    return $query->where('purchase_order_id', $purchaseOrderId);
  }

  public function scopeByReason($query, $reason)
  {
    return $query->where('reason', $reason);
  }

  // Methods
  public function calculateTotals(): void
  {
    $this->subtotal = $this->details()->sum('subtotal');
    $this->tax_amount = $this->details->sum(function ($detail) {
      return ($detail->subtotal * $detail->tax_rate) / 100;
    });
    $this->total = $this->subtotal + $this->tax_amount;
    $this->save();
  }
}
