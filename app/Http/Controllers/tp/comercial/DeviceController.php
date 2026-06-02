<?php

namespace App\Http\Controllers\tp\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\tp\comercial\DeviceAssignmentService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\tics\Equipment;
use App\Models\tp\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeviceController extends Controller
{
  protected $deviceAssignmentService;

  public function __construct(DeviceAssignmentService $deviceAssignmentService)
  {
    $this->deviceAssignmentService = $deviceAssignmentService;
  }

  private function getAuthenticatedDriver(): ?Worker
  {
    $user = Auth::user();
    if ($user && $user->partner_id) {
      return Worker::find($user->partner_id);
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
          'success' => true,
          'data' => [
            'is_active' => false,
            'serial' => null,
            'equipment_id' => null,
            'equipment_name' => null
          ]
        ]);
      }

      $status = $this->deviceAssignmentService->getDeviceStatus($driver->id);

      return response()->json([
        'success' => true,
        'data' => $status
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
    return $this->autoActivate($request);
  }

  public function autoActivate(Request $request)
  {
    $driver = $this->getAuthenticatedDriver();

    if (!$driver) {
      return response()->json([
        'success' => false,
        'message' => 'Conductor no encontrado'
      ], 404);
    }

    // Buscar dispositivo asignado en TICS
    $equipment = $this->deviceAssignmentService->getAssignedEquipmentByDriver($driver->id);

    if (!$equipment) {
      return response()->json([
        'success' => false,
        'message' => 'No tiene ningún dispositivo asignado. Contacte al administrador.'
      ], 400);
    }

    // Verificar que sea un celular válido
    if ($equipment->tipo_equipo_id !== 3) {
      return response()->json([
        'success' => false,
        'message' => 'El dispositivo asignado no es un teléfono móvil válido para tracking'
      ], 400);
    }

    return response()->json([
      'success' => true,
      'message' => 'Dispositivo activado correctamente',
      'data' => [
        'is_active' => true,
        'serial' => $equipment->serie,
        'equipment_name' => $equipment->equipo,
        'equipment_id' => $equipment->id
      ]
    ]);
  }

  /**
   * Desactivar dispositivo del conductor
   */
  public function unregister(Request $request)
  {
    $driver = $this->getAuthenticatedDriver();

    if (!$driver) {
      return response()->json([
        'success' => false,
        'message' => 'Conductor no encontrado'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Dispositivo desactivado correctamente'
    ]);
  }


  public function validateSerial(Request $request)
  {
    $request->validate([
      'serial' => 'required|string'
    ]);

    $equipment = Equipment::where('serie', $request->serial)
      ->where('status_deleted', 1)
      ->where('tipo_equipo_id', 3)
      ->first();

    if (!$equipment) {
      return response()->json([
        'success' => false,
        'valid' => false,
        'message' => 'Dispositivo no válido o no es un teléfono móvil'
      ]);
    }

    return response()->json([
      'success' => true,
      'valid' => true,
      'data' => [
        'equipment_id' => $equipment->id,
        'equipment_name' => $equipment->equipo,
        'serial' => $equipment->serie
      ]
    ]);
  }

  public function getEquipment(Request $request)
  {
    try {
      $request->validate([
        'serial' => 'required|string|min:5|max:50',
      ]);

      $equipment = Equipment::where('serie', $request->serial)
        ->where('status_deleted', 1)
        ->first();

      if (!$equipment) {
        return response()->json([
          'success' => false,
          'message' => 'Equipo no encontrado'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => $equipment
      ]);
    } catch (Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => $th->getMessage()
      ], 500);
    }
  }

  public function byDeviceId($deviceId)
  {
    $driver = Driver::where('device_id', $deviceId)
      ->with('latestLocation')
      ->first();

    if (!$driver) {
      return response()->json([
        'success' => false,
        'message' => 'Conductor no encontrado para el device_id proporcionado'
      ], 404);
    }

    $isAssigned = app(DeviceAssignmentService::class)->isEquipmentAssignedToDriver($deviceId, $driver->id);

    if (!$isAssigned) {
      return response()->json([
        'success' => false,
        'message' => 'El dispositivo no está asignado actualmente a este conductor'
      ], 403);
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


}
