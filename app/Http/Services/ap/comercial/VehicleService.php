<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Throwable;

class VehicleService extends BaseService implements BaseServiceInterface
{
  /**
   * Lista vehículos con filtros, búsqueda y paginación
   * @param Request $request
   * @return mixed
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Vehicles::class,
      $request,
      Vehicles::$filters,
      Vehicles::$sorts,
      VehiclesResource::class,
      ['model', 'color', 'engineType', 'status', 'sede', 'warehousePhysical', 'vehicleMovements']
    );
  }

  /**
   * Busca un vehículo por ID
   * @param $id
   * @return Vehicles
   * @throws Exception
   */
  public function find($id): Vehicles
  {
    $vehicle = Vehicles::where('id', $id)->first();
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }
    return $vehicle;
  }

  /**
   * Crea un nuevo vehículo
   * @param mixed $data
   * @return Vehicles
   * @throws Exception|Throwable
   */
  public function store(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      // Enriquecer datos del vehículo
      $data = $this->enrichData($data);

      // Crear el vehículo
      $vehicle = Vehicles::create($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Enriquece los datos del vehículo antes de crear
   * @param mixed $data
   * @return mixed
   * @throws Exception
   */
  protected function enrichData(mixed $data)
  {
    // Establecer estado inicial del vehículo
    if (!isset($data['ap_vehicle_status_id'])) {
      $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
    }

    // Validar que el VIN no exista
    $existingVehicle = Vehicles::where('vin', $data['vin'])
      ->whereNull('deleted_at')
      ->where('status', 1)
      ->first();

    if ($existingVehicle) {
      throw new Exception("El VIN {$data['vin']} ya existe en el sistema");
    }

    // Validar que el número de motor no exista
    $existingEngine = Vehicles::where('engine_number', $data['engine_number'])
      ->whereNull('deleted_at')
      ->where('status', 1)
      ->first();

    if ($existingEngine) {
      throw new Exception("El número de motor {$data['engine_number']} ya existe en el sistema");
    }

    if (!$data['type_operation_id']) $data['type_operation_id'] = Constants::TYPE_OPERATION_POSTVENTA_ID;

    return $data;
  }


  /**
   * Muestra un vehículo por ID
   * @param int $id
   * @return VehiclesResource
   * @throws Exception
   */
  public function show(int $id): JsonResource
  {
    $vehicle = $this->find($id);
    return new VehiclesResource($vehicle);
  }

  /**
   * Actualiza un vehículo
   * @param mixed $data
   * @return Vehicles
   * @throws Exception|Throwable
   */
  public function update(mixed $data): JsonResource
  {
    DB::beginTransaction();
    try {
      $vehicle = $this->find($data['id']);

      // Si se actualiza el VIN, validar que no exista
      if (isset($data['vin']) && $data['vin'] !== $vehicle->vin) {
        $existingVehicle = Vehicles::where('vin', $data['vin'])
          ->where('id', '!=', $vehicle->id)
          ->whereNull('deleted_at')
          ->first();

        if ($existingVehicle) {
          throw new Exception("El VIN {$data['vin']} ya existe en el sistema");
        }
      }

      // Si se actualiza el número de motor, validar que no exista
      if (isset($data['engine_number']) && $data['engine_number'] !== $vehicle->engine_number) {
        $existingEngine = Vehicles::where('engine_number', $data['engine_number'])
          ->where('id', '!=', $vehicle->id)
          ->whereNull('deleted_at')
          ->first();

        if ($existingEngine) {
          throw new Exception("El número de motor {$data['engine_number']} ya existe en el sistema");
        }
      }

      $vehicle->update($data);

      DB::commit();
      return VehiclesResource::make($vehicle);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina un vehículo (soft delete)
   * @param $id
   * @return void
   * @throws Exception|Throwable
   */
  public function destroy($id): array
  {
    DB::beginTransaction();
    try {
      $vehicle = $this->find($id);
      $vehicle->delete();
      DB::commit();
      return ['message' => 'Vehículo eliminado correctamente'];
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
