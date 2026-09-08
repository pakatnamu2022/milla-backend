<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\Driver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplyControl extends BaseModel
{
    protected $table = 'op_supply_control';

    public $timestamps = true;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'supplier_id',
        'mileage',
        'gallons',
        'photo_id',
        'is_base',
        'recorded_at',
        'created_by',
        'status_deleted',
    ];

    protected $casts = [
        'mileage' => 'decimal:2',
        'gallons' => 'decimal:3',
        'is_base' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    const filters = [
        'search' => ['vehicle.placa', 'driver.nombre_completo', 'supplier.name'],
        'vehicle_id' => '=',
        'driver_id' => '=',
        'supplier_id' => '=',
        'is_base' => '=',
        'recorded_at' => 'between',
        'status_deleted' => '=',
    ];

    const sorts = [
        'recorded_at',
        'mileage',
        'gallons',
        'created_at',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function photo(): HasOne
    {
        return $this->hasOne(SupplyPhoto::class, 'supply_control_id', 'id')
            ->where('photo_type', SupplyPhoto::TYPE_TICKET);
    }

    public function scopeDriverRole($query)
    {
        return $query->whereHas('driver', function ($q) {
            $q->whereIn('cargo_id', [11, 12]);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status_deleted', 1);
    }

    // Scope para en base
    public function scopeInBase($query)
    {
        return $query->where('is_base', 1);
    }

    // Scope para fuera de base
    public function scopeOutOfBase($query)
    {
        return $query->where('is_base', 0);
    }
}
