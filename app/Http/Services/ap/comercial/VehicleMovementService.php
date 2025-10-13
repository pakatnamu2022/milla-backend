<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehicleMovementResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class VehicleMovementService extends BaseService implements BaseServiceInterface
{
  /**
   * List all vehicle movements with filtering, sorting, and pagination
   */
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      VehicleMovement::class,
      $request,
      VehicleMovement::filters,
      VehicleMovement::sorts,
      VehicleMovementResource::class
    );
  }

  /**
   * Find a specific vehicle movement by ID
   * @throws Exception
   */
  public function find($id): VehicleMovement
  {
    $vehicleMovement = VehicleMovement::where('id', $id)->first();
    if (!$vehicleMovement) {
      throw new Exception('Movimiento de vehículo no encontrado');
    }
    return $vehicleMovement;
  }

  /**
   * Create a new vehicle movement
   * @throws Exception|Throwable
   */
  public function store(mixed $data): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      // Validate that the purchase order exists if provided
      if (isset($data['ap_vehicle_purchase_order_id'])) {
        $vehiclePurchaseOrder = VehiclePurchaseOrder::find($data['ap_vehicle_purchase_order_id']);
        if (!$vehiclePurchaseOrder) {
          throw new Exception('Orden de compra de vehículo no encontrada');
        }
      }
      $vehicleMovement = VehicleMovement::create($data);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement when a vehicle purchase order is created
   * @throws Exception|Throwable
   */
  public function storeRequestedVehicleMovement($vehiclePurchaseOrderId): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehiclePurchaseOrder = VehiclePurchaseOrder::find($vehiclePurchaseOrderId);
      $vehicleMovement = VehicleMovement::create([
        'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN,
        'ap_vehicle_purchase_order_id' => $vehiclePurchaseOrder->id,
        'movement_date' => now(),
        'observation' => 'Creación de orden de compra de vehículo',
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Show details of a specific vehicle movement
   * @throws Exception
   */
  public function show($id): VehicleMovementResource
  {
    return new VehicleMovementResource($this->find($id));
  }

  /**
   * Update an existing vehicle movement
   * @throws Exception|Throwable
   */
  public function update(mixed $data): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehicleMovement = $this->find($data['id']);

      // Validate that the purchase order exists if provided
      if (isset($data['ap_vehicle_purchase_order_id'])) {
        $vehiclePurchaseOrder = VehiclePurchaseOrder::find($data['ap_vehicle_purchase_order_id']);
        if (!$vehiclePurchaseOrder) {
          throw new Exception('Orden de compra de vehículo no encontrada');
        }
      }

      $vehicleMovement->update($data);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Delete a vehicle movement
   * @throws Exception|Throwable
   */
  public function destroy($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $vehicleMovement = $this->find($id);
      $vehicleMovement->delete();
      DB::commit();
      return response()->json(['message' => 'Movimiento de vehículo eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }
}
