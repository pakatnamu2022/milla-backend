<?php

namespace App\Models\ap\comercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApReceivingInspection extends Model
{
    use SoftDeletes;

    protected $table = 'ap_receiving_inspection';

    protected $fillable = [
        'shipping_guide_id',
        'photo_front_url',
        'photo_back_url',
        'photo_left_url',
        'photo_right_url',
        'general_observations',
        'inspected_by',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($inspection) {
            $inspection->damages()->delete();
        });
    }

    public function setGeneralObservationsAttribute($value): void
    {
        $this->attributes['general_observations'] = $value ? strtoupper($value) : null;
    }

    public function damages(): HasMany
    {
        return $this->hasMany(ApReceivingInspectionDamage::class, 'receiving_inspection_id');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function shippingGuide(): BelongsTo
    {
        return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
    }
}
