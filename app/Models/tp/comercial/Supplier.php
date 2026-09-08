<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseModel
{
    protected $table = 'op_supplier';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'ruc',
        'address',
        'phone',
        'is_active',
        'created_by',
        'status_deleted',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplies(): HasMany
    {
        return $this->hasMany(SupplyControl::class, 'supplier_id');
    }
}
