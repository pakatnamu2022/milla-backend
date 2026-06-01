<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\gp\tics\Equipment;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\tp\comercial\DriverLocation;
use App\Models\tp\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverLocationService extends BaseService
{
    protected $deviceAssignmentService;

    public function __construct(DeviceAssignmentService $deviceAssignmentService)
    {
        $this->deviceAssignmentService = $deviceAssignmentService;
    }
    public function registerLocation(array $data)
    {
        return DB::transaction(function() use ($data) {
           
            $equipment = Equipment::where('serie', $data['device_id'])
                ->where('status_deleted', 1)
                ->where('tipo_equipo_id', 3) // Solo celulares
                ->first();

            if (!$equipment) {
                Log::warning('Intento de envío desde equipo no registrado', [
                    'serial' => $data['device_id']
                ]);
                throw new \Exception('El dispositivo no está registrado en el sistema TICS');
            }

            $assignment = EquipmentAssigment::where('status_deleted', 1)
                ->whereNull('unassigned_at')
                ->whereHas('items', function($q) use ($equipment) {
                    $q->where('equipo_id', $equipment->id);
                })
                ->first();

            if (!$assignment) {
                Log::warning('Intento de envío desde equipo no asignado', [
                    'serial' => $data['device_id'],
                    'equipment_id' => $equipment->id,
                    'equipment_name' => $equipment->equipo
                ]);
                throw new \Exception('El dispositivo no está asignado actualmente a ningún conductor');
            }
            $driver = Driver::find($assignment->persona_id);

            if (!$driver) {
                Log::error('Conductor no encontrado para asignación', [
                    'assignment_id' => $assignment->id,
                    'persona_id' => $assignment->persona_id
                ]);
                throw new \Exception('El conductor asociado al dispositivo no existe');
            }

            Log::info('Ubicación recibida correctamente', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->nombre_completo,
                'serial' => $data['device_id'],
                'assignment_id' => $assignment->id
            ]);

            // 4. Procesar la ubicación (sin usar device_id de rrhh_persona)
            $reportedAt = isset($data['timestamp']) 
                ? \Carbon\Carbon::parse($data['timestamp'])->setTimeZone('America/Lima') 
                : now();

            $location = DriverLocation::updateOrCreate(
                ['driver_id' => $driver->id],
                [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? null,
                    'speed' => $data['speed'] ?? null,
                    'battery_level' => $data['battery_level'] ?? null,
                    'reported_at' => $reportedAt
                ]
            );

            // 5. Actualizar el estado del conductor
            $driver->updateStatus();

            return $location;

        });
    }

    public function list(Request $request)
    {
        $locations = DriverLocation::with('driver')
              ->when($request->driver_id, function($query, $driverId) {
                return $query->where('driver_id', $driverId);
              })
              ->when($request->from_date, function($query, $date) {
                 return $query->where('reported_at', '>=', $date);
              })
              ->when($request->to_date, function($query, $date) {
                return $query->where('reported_at', '<=', $date);
              })
              ->orderBy('reported_at', 'desc')
              ->paginate($request->per_page ?? 50);
        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    public function getLatestForAllDrivers()
    {
        return DriverLocation::with('driver')
              ->orderBy('reported_at', 'desc')
              ->get();
    }

    public function getLatestForDriver($driverId)
    {
        Log::info("ID DEL DRIVER", [
            'driver' => $driverId
        ]);

        try{
            
            Log::info("DRIVER LOCATION", [
            'DATOS DEL DRIVER' => DriverLocation::where('driver_id', $driverId)
               ->with('driver')
               ->first()
            ]);
            return DriverLocation::where('driver_id', $driverId)
                ->with('driver')
                ->first();

        }catch(\Exception $e){
            Log::error("Error al obtener ubicación del driver", [
                'driver_id' => $driverId,
                'error' => $e->getMessage()
            ]);
        }
        

    }

    public function latest()
    {
        $locations = $this->getLatestForAllDrivers();

        return response()->json([
            'success'=> true,
            'data'=> $locations->map(function($location){
                return [
                    'driver_id' => $location->driver_id,
                    'driver_name' => $location->driver->nombre_completo ?? 'Desconocido',
                    'driver_code' => $location->driver->vat ?? null,
                    'coordinates' => $location->coordinates,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'reported_at' => $location->reported_at,
                    'time_ago' => $location->time_ago,
                    'status' => $location->status,
                    'status_color' => $location->status_color,
                    'battery_level' => $location->battery_level,
                    'google_maps_url' => $location->google_maps_url
                ];
            })
        ]);
    }

    public function show($driverId)
    {
        $location = $this->getLatestForDriver($driverId);

        if(!$location){
            return response()->json([
                'success'=> false,
                'message'=> 'No hay ubicacion para este conductor'
            ], 400);
        }

        return response()->json([
            'success'=> true,
            'data'=> $location
        ]);
    }
}