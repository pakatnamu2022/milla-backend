<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\Driver;

class DriverLocation extends BaseModel
{
    protected $table = 'driver_location';

    protected $fillable = [
        'driver_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'battery_level',
        'reported_at'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'float',
        'speed' => 'float',
        'battery_level' => 'float',
        'reported_at' => 'datetime'
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function getTimeAgoAttribute()
    {
        return $this->reported_at->diffForHumans();
    }

    public function getStatusAttribute()
    {
        $minutesDiff = now()->diffInMinutes($this->reported_at);
        $activeThreshold = config('monitoreo.active_threshold', 5);
        $inactiveThreshold = config('monitoreo.inactive_threshold', 30);

        if($minutesDiff <= $activeThreshold){
            return 'active';
        }elseif($minutesDiff <= $inactiveThreshold){
            return 'inactive';
        }else{
            return 'disconnected';
        }
    }
    
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'yellow',
            'disconnected' => 'red',
            default => 'gray'
        };
    }

    public function getCoordinatesAttribute()
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    public function getGoogleMapsUrlAttribute()
    {
        return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }
}