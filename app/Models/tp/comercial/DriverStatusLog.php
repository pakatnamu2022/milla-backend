<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\Driver;

class DriverStatusLog extends BaseModel
{
    protected $table = 'driver_status_log';

    protected $fillable = [
        'driver_id',
        'status',
        'changed_at'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}