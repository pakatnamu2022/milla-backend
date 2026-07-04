<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\gp\tics\Equipment;
use App\Models\gp\tics\EquipmentAssigment;
use App\Models\tp\comercial\DriverLocation;
use App\Models\tp\comercial\DriverLocationHistory;
use App\Models\tp\Driver;
use Exception;
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
                ->where('tipo_equipo_id', 3)
                ->first();

            if (!$equipment) {
                throw new \Exception('El dispositivo no está registrado en el sistema TICS');
            }

            $assignment = EquipmentAssigment::where('status_deleted', 1)
                ->whereNull('unassigned_at')
                ->whereHas('items', function($q) use ($equipment) {
                    $q->where('equipo_id', $equipment->id);
                })
                ->first();

            if (!$assignment) {
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

            // 4. Procesar la ubicación 
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
            if(config('monitoreo.history_enabled', true)){
                DriverLocationHistory::create([
                    'driver_id' => $driver->id,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? null,
                    'speed' => $data['speed'] ?? null,
                    'battery_level' => $data['battery_level'] ?? null,
                    'reported_at' => $reportedAt
                ]);

                $this->cleanHistory();
            }


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
        try{
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

    private function cleanHistory(): void
    {
        if(!config('monitoreo.auto_cleanup_enabled', true)){
            return;
        }

        $key = 'location_history_cleanup_counter';
        $lastRunKey = 'location_history_last_cleanup';

        $count = cache()->increment($key, 1);
        $lastRun = cache()->get($lastRunKey, 0);
        $currentTime = time();

        $shouldClean = ($count >= 100) || ($currentTime - $lastRun >= 3600);

        if($shouldClean){
            try{
                $days = config('monitoreo.history_retention_days', 7);

                // eliminar registros con mas de 7 dias de antiguedad
                $deleted = DriverLocationHistory::where('reported_at', '<', now()->subDays($days))
                           ->delete();
                
                cache()->put($key, 0, now()->addDays(1));
                cache()->put($lastRunKey, $currentTime, now()->addDays(1));

            }catch(Exception $e){
                 Log::error('Error en limpieza de historial', [
                    'error' => $e->getMessage()
                ]);
            }
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

    public function history($driverId, Request $request){
        $hours = $request->input('hours', 2);
        $limit = $request->input('limit', 1000);

        $locations = DriverLocationHistory::forDriver($driverId)
            ->lastHours($hours)
            ->orderBy('reported_at', 'desc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $locations,
            'meta' => [
                'driver_id' => $driverId,
                'hours' => $hours,
                'total' => $locations->count()
            ]
        ]);
    }


}