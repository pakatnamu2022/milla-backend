<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\gp\tics\Equipment;
use App\Models\tp\Driver;
use Exception;
use Illuminate\Support\Facades\Log;

class DeviceValidationService
{
    public function validateDevice(string $imei): Equipment
    {
        $equipment = Equipment::where('serie', $imei)
                    ->where('status_deleted', 1)
                    ->first();
        if(!$equipment){
            throw new Exception('El dispositivo no está registrado en el sistema como equipo válido');
        }

        if ($equipment->tipo_equipo_id !== 3) {
            throw new Exception('El dispositivo no es un teléfono móvil válido para tracking');
        }
        return $equipment;

    }

    public function isDeviceAvailable(string $serial, ?int $excludeDriverId = null): bool
    {
        $query = Driver::where('device_id', $serial)
            ->whereNotNull('device_id');
        
        if ($excludeDriverId) {
            $query->where('id', '!=', $excludeDriverId);
        }
        
        return !$query->exists();
    }


    public function registerDevice(int $driverId, string $serial): Driver
    {
        $driver = Driver::findOrFail($driverId);

        //validar IMEI

        $equipment = $this->validateDevice($serial);

        //actualizar device_id
        if (!$this->isDeviceAvailable($serial, $driverId)) {
            throw new Exception('El dispositivo ya está asignado a otro conductor. Contacte al administrador.');
        }

        $oldSerial = $driver->device_id;
        $driver->update(['device_id' => $serial]);

        Log::info("Dispositivo registrado", [
            'driver_id' => $driverId,
            'driver_name' => $driver->nombre_completo,
            'old_serial' => $oldSerial,
            'new_serial' => $serial,
            'equipment_id' => $equipment->id,
        ]);
        
        return $driver;
    }

    public function unregisterDevice(int $driverId): Driver
    {
        $driver = Driver::findOrFail($driverId);
        $driver->update(['device_id' => null]);

        return $driver;
    }
    public function getEquipmentBySerial(string $serial): ?Equipment
    {
        return Equipment::where('serie', $serial)
            ->where('status_deleted', 1)
            ->first();
    }
}

