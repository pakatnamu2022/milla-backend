<?php

namespace App\Models\ap\compras;

use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReceptionDetail extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_reception_details';

    protected $fillable = [
        'purchase_reception_id',
        'purchase_order_item_id',
        'product_id',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'reception_type',
        'unit_cost',
        'is_charged',
        'total_cost',
        'rejection_reason',
        'rejection_notes',
        'bonus_reason',
        'batch_number',
        'expiration_date',
        'notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'quantity_accepted' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_charged' => 'boolean',
        'expiration_date' => 'date',
    ];

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

    public function getHasRejectedQuantityAttribute(): bool
    {
        return $this->quantity_rejected > 0;
    }

    public function getIsFullyAcceptedAttribute(): bool
    {
        return $this->quantity_accepted == $this->quantity_received;
    }

    public function getAcceptanceRateAttribute(): float
    {
        if ($this->quantity_received == 0) {
            return 0;
        }
        return ($this->quantity_accepted / $this->quantity_received) * 100;
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

    public function scopeWithRejections($query)
    {
        return $query->where('quantity_rejected', '>', 0);
    }

    public function scopeFullyAccepted($query)
    {
        return $query->whereColumn('quantity_accepted', '=', 'quantity_received');
    }

    public function scopeCharged($query)
    {
        return $query->where('is_charged', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_charged', false);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}