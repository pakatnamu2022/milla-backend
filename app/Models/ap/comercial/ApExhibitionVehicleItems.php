<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApExhibitionVehicleItems extends Model
{
    use SoftDeletes;

    protected $table = 'ap_exhibition_vehicle_items';

    protected $fillable = [
        'exhibition_vehicle_id',
        'item_type',
        'vehicle_id',
        'description',
        'quantity',
        'observaciones',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'status' => 'boolean',
    ];

    // Relationships
    public function exhibitionVehicle(): BelongsTo
    {
        return $this->belongsTo(ApExhibitionVehicles::class, 'exhibition_vehicle_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicles::class, 'vehicle_id');
    }

    // Scopes
    public function scopeVehicles($query)
    {
        return $query->where('item_type', 'vehicle');
    }

    public function scopeEquipment($query)
    {
        return $query->where('item_type', 'equipment');
    }

    // Accessors
    public function getIsVehicleAttribute(): bool
    {
        return $this->item_type === 'vehicle';
    }

    public function getIsEquipmentAttribute(): bool
    {
        return $this->item_type === 'equipment';
    }
}
