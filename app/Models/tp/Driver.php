<?php

namespace App\Models\tp;

use App\Http\Services\tp\comercial\DeviceAssignmentService;
use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\tp\comercial\DriverLocation;
use App\Models\tp\comercial\DriverStatusLog;
use Illuminate\Database\Eloquent\Builder;

class Driver extends BaseModel
{
    protected $table = "rrhh_persona";

      protected $fillable = [
        'id',
        'vat',
        'nombre_completo',
        'sede_id',
        'jefe_id',
        'email',
        'email2',
        'email3',
    ];

    const filters = [
    'search' => ['nombre_completo', 'vat'],
    'vat' => 'like',
    'sede.empresa_id' => '=',
    'nombre_completo' => 'like',
    'cargo_id' => 'in',
    'status_id' => '=',
    'sede_id' => '=',
    'sede.departamento' => '=',
    ];

    const sorts = [
    'nombre_completo',
    ];

    protected static function booted()
    {
        static::addGlobalScope('activeDriver', function (Builder $builder){
            $builder->where('status_deleted', 1)
                    ->where('b_empleado', 1)
                    ->where('status_id', 22)
                    ->whereIn('cargo_id',[11,12]);
        });
    }

    protected function sede()
    {
        return $this->hasOne(Sede::class, 'id', 'sede_id');
    }

    public function latestLocation()
    {
        return $this->hasOne(DriverLocation::class, 'driver_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(DriverStatusLog::class, 'driver_id');
    }

    public function getFullNameAttribute()
    {
        return $this->nombre_completo ?? '';
    }

    public function getCurrentStatusAttribute()
    {
        if(!$this->latestLocation){
            return 'nodata';
        }
         $reportedAt = $this->latestLocation->reported_at;
        $now = now();
        
        $reportedTimestamp = $reportedAt->timestamp;
        $nowTimestamp = $now->timestamp;
        $minutesDiff = floor(($nowTimestamp - $reportedTimestamp) / 60);

        $activeThreshold = config('monitoreo.active_threshold', 5);
        $inactiveThreshold = config('monitoreo.inactive_threshold', 30);

        if($minutesDiff <= $activeThreshold){
            return 'active';
        }elseif ($minutesDiff <= $inactiveThreshold){
            return 'inactive';
        }else{
            return 'disconnected';
        }
    }

    public function getStatusColorAttribute(){
        return match($this->current_status){
            'active' => 'green',
            'inactive' => 'yellow',
            'disconnected' => 'red',
            'nodata' => 'gray',
            default => 'gray'
        };
    }

    public function getStatusTextAttribute(){
        return match($this->current_status){
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'disconnected' => 'Desconectado',
            'nodata' => 'Sin datos',
            default => 'Sin Datos'
        };
    }

    public function getLastLocationAttribute()
    {
        if(!$this->latestLocation){
            return null;
        }

         return [
            'coordinates' => $this->latestLocation->coordinates,
            'latitude' => $this->latestLocation->latitude,
            'longitude' => $this->latestLocation->longitude,
            'reported_at' => $this->latestLocation->reported_at,
            'time_ago' => $this->latestLocation->reported_at->diffForHumans(),
            'accuracy' => $this->latestLocation->accuracy,
            'battery_level' => $this->latestLocation->battery_level,
            'google_maps_url' => $this->latestLocation->google_maps_url
        ];
    }

    public function getLastUpdateMinutesAttribute()
    {
        if(!$this->latestLocation){
            return PHP_INT_MAX;
        }
        return now()->diffInMinutes($this->latestLocation->reported_at);
    }

    public function scopeWhereStatus($query, $status){
        switch($status){
            case 'active':
                return $query->whereHas('latestLocation', function($q) {
                    $q->where('reported_at', '>=', now()->subMinutes(config('monitoreo.active_threshold', 5)));

                });
            case 'inactive':
                return $query->whereHas('latestLocation', function($q){
                    $q->whereBetween('reported_at', [
                        now()->subMinutes(config('monitoreo.inactive_threshold', 30)),
                        now()->subMinutes(config('monitoreo.active_threshold', 5))
                    ]);
                });
            case 'disconnected':
                return $query->whereHas('latestLocation', function($q) {
                    $q->where('reported_at', '<', now()->subMinutes(config('monitoreo.inactive_threshold', 30)));
                });
            case 'nodata':
                return $query->whereDoesntHave('latestLocation');
            default:
                return $query;
        }
    }

    public function scopeActive($query){
        return $query->whereHas('latestLocation', function($q){
            $q->where('reported_at', '>=', now()->subMinutes(config('monitoreo.active_threshold', 5)));
        });
    }

    public function scopeWithLocation($query){
        return $query->has('latestLocation');
    }

    public function scopeWithoutLocation($query){
        return $query->doesntHave('latestLocation');
    }

    public function updateStatus()
    {

        $latestLocation = $this->latestLocation;

        if(!$latestLocation){
            $newStatus = 'disconnected';
        }else{
            // $nowTimestamp = now()->timestamp;
            // $reportedTimestamp = $latestLocation->reported_at->timestamp;
            // $minutesDiff = floor(($nowTimestamp - $reportedTimestamp) / 60);

            $reportedAt = $latestLocation->reported_at;
            $now = now();

            $reportedAtTimestamp = $reportedAt->timestamp;
            $nowTimestamp = $now->timestamp;
            $minutesDiff = floor(($nowTimestamp - $reportedAtTimestamp) / 60);

            $activeThreshold = config('monitoreo.active_threshold', 5);
            $inactiveThreshold = config('monitoreo.inactive_threshold', 30);

            if($minutesDiff <= $activeThreshold){
                $newStatus = 'active';
            }elseif($minutesDiff <= $inactiveThreshold){
                $newStatus = 'inactive';
            }else{
                $newStatus = 'disconnected';
            }

        }

        //obtener el ultimo log de estado para este conductor
        $lastLog = $this->statusLogs()->latest('changed_at')->first();

        //solo registrar si cambio de estado
        if(!$lastLog || $lastLog->status !== $newStatus){
            DriverStatusLog::create([
                'driver_id' => $this->id,
                'status' => $newStatus,
                'changed_at' => $latestLocation ? $latestLocation->reported_at : now()
            ]);
        }
        return $newStatus;
    }

    public function getDashboardData()
    {
        return [
            'id' => $this->id,
            'code' => $this->vat,
            'name' => $this->full_name,
            'status' => $this->current_status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'last_location' => $this->last_location,
            'device_id' => $this->device_id,
            'is_active' => true
        ];
    }

    public function getAssignedDevice()
    {
        $service = app(DeviceAssignmentService::class);
        return $service->getAssignedEquipmentByDriver($this->id);
    }
    public function hasDeviceAssigned(): bool
    {
        return !is_null($this->getAssignedDevice());
    }
    public function getDeviceSerialAttribute(): ?string
    {
        $device = $this->getAssignedDevice();
        return $device?->serie;
    }


}