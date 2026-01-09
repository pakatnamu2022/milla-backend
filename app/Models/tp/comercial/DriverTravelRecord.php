<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;


class DriverTravelRecord extends BaseModel
{
    protected $table = 'driver_travel_record';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'dispatch_id',
        'driver_id',
        'record_type',
        'recorded_at',
        'recorded_mileage',
        'notes',
        'device_id',
        'sync_status', 
    ];
    protected $casts = [
        'recorded_at' => 'datetime',
        'recorded_mileage' => 'decimal:2',
    ];

    public function dispatch()
    {
        return $this->belongsTo(TravelControl::class, 'dispatch_id');
    }
    
    public function driver()
    {
        return $this->belongsTo(Person::class, 'driver_id', 'id');
    }
}
