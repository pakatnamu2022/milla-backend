<?php

namespace App\Http\Services\tp\comercial;

use App\Models\gp\tics\Equipment;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\gp\tics\EquipmentItemAssigment;
use App\Models\tp\Driver;
use Exception;
use Illuminate\Support\Facades\Log;

class DeviceAssignmentService
{
    const MOBILE_EQUIPMENT_TYPE_ID = 3;
    const EQUIPMENT_ACTIVE_STATUS = 1;
    const ASSIGNMENT_ACTIVE_STATUS = 1;

    /**
     * Verificar si un equipo está asignado activamente a algún conductor
     * Retorna el conductor asignado o null
     */
    public function getDriverByEquipment(string $serial): ?Driver
    {
        $equipment = Equipment::where('serie', $serial)
            ->where('status_deleted', self::EQUIPMENT_ACTIVE_STATUS)
            ->where('tipo_equipo_id', self::MOBILE_EQUIPMENT_TYPE_ID)
            ->first();

        if (!$equipment) {
            return null;
        }

        $assignment = EquipmentAssigment::where('status_deleted', self::ASSIGNMENT_ACTIVE_STATUS)
            ->whereNull('unassigned_at')
            ->whereHas('items', function ($q) use ($equipment) {
                $q->where('equipo_id', $equipment->id);
            })
            ->first();

        if (!$assignment || !$assignment->persona_id) {
            return null;
        }

        return Driver::find($assignment->persona_id);
    }

    /**
     * Verificar si un equipo está asignado activamente
     */
    public function isEquipmentAssigned(string $serial): bool
    {
        $equipment = Equipment::where('serie', $serial)
            ->where('status_deleted', self::EQUIPMENT_ACTIVE_STATUS)
            ->where('tipo_equipo_id', self::MOBILE_EQUIPMENT_TYPE_ID)
            ->first();

        if (!$equipment) {
            return false;
        }

        $assignment = EquipmentAssigment::where('status_deleted', self::ASSIGNMENT_ACTIVE_STATUS)
            ->whereNull('unassigned_at')
            ->whereHas('items', function ($q) use ($equipment) {
                $q->where('equipo_id', $equipment->id);
            })
            ->exists();

        return $assignment;
    }

    /**
     * Obtener el equipo asignado actualmente a un conductor
     */
    public function getAssignedEquipmentByDriver(int $driverId): ?Equipment
    {

       
        $assignment = EquipmentAssigment::where('status_deleted', self::ASSIGNMENT_ACTIVE_STATUS)
            ->whereNull('unassigned_at')
            ->where('persona_id', $driverId)
            ->first();

        if (!$assignment) {
            return null;
        }

        $item = EquipmentItemAssigment::where('asig_equipo_id', $assignment->id)
            ->whereHas('equipment', function($q) {
                $q->where('tipo_equipo_id', self::MOBILE_EQUIPMENT_TYPE_ID);
            })
            ->first();

        if (!$item || !$item->equipment) {
            return null;
        }

        return Equipment::where('id', $item->equipo_id)
            ->where('status_deleted', self::EQUIPMENT_ACTIVE_STATUS)
            ->where('tipo_equipo_id', self::MOBILE_EQUIPMENT_TYPE_ID) 
            ->first();
    }

    /**
     * Obtener estado del dispositivo del conductor (basado en TICS)
     */
    public function getDeviceStatus(int $driverId): array
    {
        $equipment = $this->getAssignedEquipmentByDriver($driverId);

        return [
            'is_active' => !is_null($equipment),
            'serial' => $equipment?->serie,
            'equipment_id' => $equipment?->id,
            'equipment_name' => $equipment?->equipo,
            'has_mobile_device' => !is_null($equipment),
        ];
    }
}