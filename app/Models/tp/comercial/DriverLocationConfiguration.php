<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;

class DriverLocationConfiguration extends BaseModel
{
    protected $table = 'driver_location_configuration';

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    protected $casts = [
        'value' => 'json'
    ];

    public static function get($key, $default = null)
    {
        $config = self::where('key', $key)->first();
        if(!$config){
            return $default;
        }
        return $config->value;
    }

    public static function set($key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
    }
}