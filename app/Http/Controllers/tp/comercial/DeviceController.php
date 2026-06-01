<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\tp\comercial\DeviceValidationService;
use App\Models\gp\tics\Equipment;
use App\Models\tp\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeviceController extends Controller
{
    protected $deviceService;


   

    public function __construct(DeviceValidationService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    private function getAuthenticatedDriver(): ?Driver
    {
        $user = Auth::user();
        if ($user && $user->partner_id) {
            return Driver::find($user->partner_id);
        }
        
        return null;
    }

    /**
     * Obtener el estado del dispositivo del conductor autenticado
     */
    public function status()
    {
        try {
            $driver = $this->getAuthenticatedDriver();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conductor no encontrado'
                ], 404);
            }

            // Buscar el equipo asociado al device_id (que es el IMEI)
            $equipment = null;
            if ($driver->device_id) {
                $equipment = Equipment::where('serie', $driver->device_id)
                    ->where('status_deleted', 1)
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_active' => !is_null($driver->device_id),
                    'serial' => $driver->device_id,
                    'equipment_id' => $equipment?->id,
                    'equipment_name' => $equipment?->equipo,
                ]
            ]);
        } catch (Throwable $th) {
            Log::error('DeviceController@status error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar/activar dispositivo del conductor
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'serial' => 'required|string|min:15|max:50',
            ]);

            $driver = $this->getAuthenticatedDriver();
            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conductor no encontrado'
                ], 404);
            }

            // Validar y registrar el dispositivo
            $result = $this->deviceService->registerDevice($driver->id, $request->serial);

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo registrado correctamente',
                'data' => [
                    'device_id' => $result->device_id
                ]
            ]);
        } catch (Throwable $th) {
            Log::error('DeviceController@register error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 400);
        }
    }

    /**
     * Desactivar dispositivo del conductor
     */
    public function unregister()
    {
        try {
            $driver = $this->getAuthenticatedDriver();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conductor no encontrado'
                ], 404);
            }

            // Desregistrar el dispositivo
            $result = $this->deviceService->unregisterDevice($driver->id);

            return response()->json([
                'success' => true,
                'message' => 'Dispositivo desactivado correctamente',
                'data' => [
                    'device_id' => $result->device_id
                ]
            ]);
        } catch (Throwable $th) {
            Log::error('DeviceController@unregister error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    
    public function validateSerial(Request $request)
    {
        try {
            $request->validate([
                'serial' => 'required|string|min:15|max:50',
            ]);

            $equipment = $this->deviceService->validateDevice($request->serial);

            return response()->json([
                'success' => true,
                'valid' => true,
                'data' => [
                    'equipment_id' => $equipment->id,
                    'equipment_name' => $equipment->equipo,
                    'serial' => $equipment->serie,
                ]
            ]);
        } catch (Throwable $th) {
            Log::error('DeviceController@register error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => $th->getMessage()
            ], 400);
        }
    }

     public function getEquipment(Request $request)
    {
        try {
            $request->validate([
                'serial' => 'required|string|min:5|max:50',
            ]);

            $equipment = $this->deviceService->getEquipmentBySerial($request->serial);

            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $equipment->id,
                    'name' => $equipment->equipo,
                    'serial' => $equipment->serie,
                    'type' => $equipment->tipo_equipo_id,
                ]
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    


}