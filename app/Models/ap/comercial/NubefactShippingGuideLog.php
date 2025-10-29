<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NubefactShippingGuideLog extends Model
{
    protected $table = 'nubefact_shipping_guide_logs';

    protected $fillable = [
        'shipping_guide_id',
        'operation',
        'request_payload',
        'response_payload',
        'http_status_code',
        'success',
        'error_message',
    ];

    protected $casts = [
        'success' => 'boolean',
        'http_status_code' => 'integer',
    ];

    public function shippingGuide(): BelongsTo
    {
        return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
    }
}
