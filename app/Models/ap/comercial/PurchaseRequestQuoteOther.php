<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequestQuoteOther extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_request_quote_others';

    protected $fillable = [
        'purchase_request_quote_id',
        'description',
        'type',
        'value',
        'is_locked',
        'amount',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function purchaseRequestQuote(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestQuote::class, 'purchase_request_quote_id');
    }
}
