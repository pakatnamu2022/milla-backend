<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehicleMovementResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class VehicleMovementService extends BaseService implements BaseServiceInterface
{
  /**
   * Listar movimientos de vehículos con filtros y paginación
   */
  public function list(Request $request)
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
   * Buscar un movimiento de vehículo por ID
   */
  public function find($id)
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

      // Update vehicle status in purchase order if applicable
      if (isset($data['ap_vehicle_purchase_order_id']) && isset($data['ap_vehicle_status_id'])) {
        $vehiclePurchaseOrder->update([
          'ap_vehicle_status_id' => $data['ap_vehicle_status_id']
        ]);
      }

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Mostrar un movimiento de vehículo específico
   */
  public function show($id)
  {
    return new VehicleMovementResource($this->find($id));
  }

  /**
   * Actualizar un movimiento de vehículo existente
   */
  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $vehicleMovement = $this->find($data['id']);

      // Validar que la orden de compra existe si se está actualizando
      if (isset($data['ap_vehicle_purchase_order_id'])) {
        $vehiclePurchaseOrder = VehiclePurchaseOrder::find($data['ap_vehicle_purchase_order_id']);
        if (!$vehiclePurchaseOrder) {
          throw new Exception('Orden de compra de vehículo no encontrada');
        }
      }

      $vehicleMovement->update($data);

      // Actualizar el estado del vehículo en la orden de compra si corresponde
      if (isset($data['ap_vehicle_status_id']) && $vehicleMovement->ap_vehicle_purchase_order_id) {
        $vehiclePurchaseOrder = VehiclePurchaseOrder::find($vehicleMovement->ap_vehicle_purchase_order_id);
        if ($vehiclePurchaseOrder) {
          $vehiclePurchaseOrder->update([
            'ap_vehicle_status_id' => $data['ap_vehicle_status_id']
          ]);
        }
      }

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Eliminar un movimiento de vehículo (soft delete)
   */
  public function destroy($id)
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
