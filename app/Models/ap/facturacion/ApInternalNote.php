<?php

namespace App\Models\ap\facturacion;

use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApInternalNote extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_internal_notes';

    protected $fillable = [
        'number',
        'work_order_id',
        'created_date',
        'closed_date',
        'status',
    ];

    protected $casts = [
        'created_date' => 'date',
        'closed_date' => 'date',
    ];

    const array filters = [
        'search' => ['number'],
        'number' => '=',
        'work_order_id' => '=',
        'status' => '=',
        'created_date' => 'date_between',
        'closed_date' => 'date_between',
    ];

    const array sorts = ['id', 'number', 'created_date', 'closed_date'];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_INVOICED = 'invoiced';

    /**
     * Boot method to auto-generate sequential number
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->number)) {
                $model->number = self::generateNextNumber();
            }
            if (empty($model->created_date)) {
                $model->created_date = now();
            }
        });
    }

    /**
     * Generate next sequential number
     */
    public static function generateNextNumber(): string
    {
        $lastNote = self::orderBy('id', 'desc')->first();

        if (!$lastNote) {
            return 'IN-00001';
        }

        // Extract number from format IN-00001
        $lastNumber = (int) str_replace('IN-', '', $lastNote->number);
        $nextNumber = $lastNumber + 1;

        return 'IN-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
    }

    public function electronicDocuments(): BelongsToMany
    {
        return $this->belongsToMany(
            ElectronicDocument::class,
            'electronic_document_internal_notes',
            'internal_note_id',
            'electronic_document_id'
        )->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInvoiced($query)
    {
        return $query->where('status', self::STATUS_INVOICED);
    }

    /**
     * Business methods
     */
    public function markAsInvoiced(?string $closedDate = null): void
    {
        $this->update([
            'status' => self::STATUS_INVOICED,
            'closed_date' => $closedDate ?? now()->toDateString(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInvoiced(): bool
    {
        return $this->status === self::STATUS_INVOICED;
    }
}