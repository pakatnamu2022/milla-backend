<?php

namespace App\Models\tp\comercial;

use App\Models\BaseModel;
use App\Models\gp\gestionsistema\Person;
use App\Models\User;

class TpTravelPhoto extends BaseModel
{
    protected $table = 'tp_travel_photo';
    protected $primaryKey = 'id';
    protected $fillable = [
        'dispatch_id',
        'driver_id',
        'photo_type', //inicio, fin, combustible, incidente
        'file_name',
        'path',
        'public_url',
        'mime_type',
        'latitude',
        'longitude',
        'user_agent',
        'operating_system',
        'browser',
        'device_model',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at'=> 'datetime',
        'updated_at' => 'datetime',
    ];

    public function travel()
    {
        return $this->belongsTo(TravelControl::class, 'dispatch_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(Person::class, 'driver_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function scopeByType($query, $tipo)
    {
        return $query->where('photo_type', $tipo);
    }

    public function scopeByTrip($query, $dispatchId)
    {
        return $query->where('dispatch_id', $dispatchId);
    }

    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeWithGeolocation($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    public function getPhotoTypeTextAttribute()
    {
        $types = [
            'start' => 'Inicio de viaje',
            'end' => 'Fin de Viaje',
            'fuel' => 'Foto de combustible',
            'incident' => 'Foto de Incidente',
            'invoice' => 'Comprobante de Gasto'
        ];

        return $types[$this->photo_type] ?? $this->photo_type;
    }


}
