<?php


namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\tp\comercial\DriverLocation;
use App\Models\tp\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverService extends BaseService
{
    public function index(Request $request)
    {
        $drivers = Driver::with('latestLocation')
             ->when($request->status, function($query, $status){
                return $query->whereStatus($status);
             })
             ->when($request->search, function($query, $search){
                return $query->where('nombre_completo', 'like', "%{$search}%")
                    ->orWhere('vat', 'like', "%{$search}%");           
            })
            ->orderBy('nombre_completo')
            ->paginate($request->per_page ?? 15);

        $drivers->getCollection()->transform(function($driver) {
            return $driver->getDashboardData();
        });

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    public function show(Driver $driver)
    {
        $driver->load('latestLocation');

        return response()->json([
            'success' => true,
            'data' => $driver->getDashboardData()
        ]);
    }

    public function stats()
    {
        $total = Driver::count();
        $active = Driver::active()->count();
        $inactive = Driver::whereStatus('inactive')->count();
        $disconnected = Driver::whereStatus('disconnected')->count();
        $withoutLocation = Driver::withoutLocation()->count();


        return response()->json([
            'success' => true,
            'data' => [
                'total_drivers' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'disconnected' => $disconnected,
                'without_location' => $withoutLocation,
                'online_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
            ]
        ]);
    }

    public function byDeviceId($deviceId)
    {
        
        $driver = Driver::where('device_id',$deviceId)
            ->with('latestLocation')
            ->first();


        if(!$driver){
            return response()->json([
                'success' => false,
                'message' => 'Conductor no encontrado para el device_id proporcionado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $driver->id,
                'code' => $driver->vat,
                'name' => $driver->full_name,
                'is_active' => true,
                'last_location' => $driver->latestLocation
            ]
        ]);
    }

    public function assignDevice(Driver $driver, $deviceId)
    {
        $existingDriver = Driver::where('device_id', $deviceId)->first();

        if(!$existingDriver && $existingDriver->id !== $driver->id)
        {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo ya está asignado a otro conductor'
            ], 400);

        }

        $driver->update(['device_id' => $deviceId]);

        return response()->json([
            'success'=> true,
            'message'=> 'Dispositivo asignado correctamente',
            'data'=> [
                'device_id' => $driver->device_id
            ]
        ]);
    }

    public function removeDevice(Driver $driver)
    {
        $driver->update(['device_id' => null]);

        DriverLocation::where('driver_id', $driver->id)->delete();

        return response()->json([
            'success'=> true,
            'message'=> 'Dispositivo removido correctamente'
        ]);
    }

    public function refreshStatus(Driver $driver)
    {
        $status = $driver->updateStatus();

        return response()->json([
            'success' => true,
            'message' => "Estado actualizado: {$status}",
            'data' => [
                'status' => $status,
                'status_text' => $driver->status_text,
                'last_location' => $driver->last_location
            ]
        ]);

    }
}