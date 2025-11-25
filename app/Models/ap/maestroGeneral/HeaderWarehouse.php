<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HeaderWarehouse extends Model
{
    use SoftDeletes;

    protected $table = 'header_warehouses';

    protected $fillable = [
        'dyn_code',
        'status',
        'is_received',
        'sede_id',
        'type_operation_id',
    ];

    const filters = [
        'search' => ['dyn_code'],
        'status' => '=',
        'is_received' => '=',
        'sede_id' => '=',
        'type_operation_id' => '=',
    ];

    const sorts = [
        'id',
        'dyn_code',
        'sede_id',
        'type_operation_id',
        'status',
    ];

    public function setDynCodeAttribute($value): void
    {
        $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function typeOperation(): BelongsTo
    {
        return $this->belongsTo(ApCommercialMasters::class, 'type_operation_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'header_warehouse_id');
    }
}
