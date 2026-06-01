<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\tp\Driver;
use App\Models\tp\comercial\DriverLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverLocationService extends BaseService
{
    public function registerLocation(array $data)
    {
        return DB::transaction(function() use ($data) {
            //observaciones
            //falta validar que el conductor este activo
            Log::info("DATOS DEL DEVICE ID", [
                'device_id' => [
                    $data['device_id']
                ]
            ]);
            $driver = Driver::where('device_id',$data['device_id'])->first();

            if(!$driver){
                throw new \Exception('Dispositivo no registrado o conductor inactivo');
            }

            //verificar que reported_at este en hora peru
            $reportedAt = isset($data['timestamp']) ? \Carbon\Carbon::parse($data['timestamp'])->setTimeZone('America/Lima') : now();

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

            //actualizar el estado del conductor

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
        Log::info("DRIVER LOCATION", [
            'DATOS DEL DRIVER' => DriverLocation::where('driver_id', $driverId)
               ->with('driver')
               ->first()
        ]);
        return DriverLocation::where('driver_id', $driverId)
               ->with('driver')
               ->first();

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