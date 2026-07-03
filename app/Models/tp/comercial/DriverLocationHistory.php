<?php


namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\tp\Driver;
use Illuminate\Database\Eloquent\Builder;

class DriverLocationHistory extends BaseModel
{
    protected $table = 'driver_location_history';

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
        'battery_level' => 'integer',
        'reported_at' => 'datetime'
    ];


    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeLastHours(Builder $query, int $hours = 2): Builder
    {
        return $query->where('reported_at', '>=', now()->subHours($hours));
    }

    public function scopeLastDays(Builder $query, int $days = 7): Builder
    {
        return $query->where('reported_at', '>=', now()->subDays($days));
    }

    public function getCoordinatesAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    public function getGoogleMapsUrlAttribute(): string
    {
         return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }

    public static function cleanOldRecords(int $days = 7): int 
    {
        return self::where('reported_at', '<', now()->subDays($days))->delete();
    }
    
}