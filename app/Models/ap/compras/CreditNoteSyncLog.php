<?php

namespace App\Models\ap\compras;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteSyncLog extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'attempted_at',
        'status',
        'credit_note_number',
        'error_message',
        'execution_time',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    /**
     * Relación a PurchaseOrder
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Scope para logs de hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('attempted_at', now()->toDateString());
    }

    /**
     * Scope para logs exitosos
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope para logs con error
     */
    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }
}
