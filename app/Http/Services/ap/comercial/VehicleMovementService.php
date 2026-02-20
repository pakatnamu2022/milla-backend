<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehicleMovementResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\Warehouse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $vehiclePurchaseOrder = PurchaseOrder::find($data['ap_vehicle_purchase_order_id']);
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
      $vehiclePurchaseOrder = PurchaseOrder::find($vehiclePurchaseOrderId);
      $vehicleMovement = VehicleMovement::create([
        'movement_type' => VehicleMovement::ORDERED,
        'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN,
        'ap_vehicle_id' => $vehiclePurchaseOrder->id,
        'observation' => 'Creación de orden de compra de vehículo',
        'movement_date' => now(),
        'previous_status_id' => null,
        'new_status_id' => ApVehicleStatus::PEDIDO_VN,
        'created_by' => auth()->id(),
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement when invoice_dynamics is set (vehicle in transit)
   * @throws Exception|Throwable
   */
  public function storeInTransitVehicleMovement($vehiclePurchaseOrderId): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehiclePurchaseOrder = PurchaseOrder::find($vehiclePurchaseOrderId);
      $vehicleId = $vehiclePurchaseOrder->vehicle->id;
      $vehicle = Vehicles::find($vehicleId);
      if (!$vehiclePurchaseOrder || !$vehicleId || !$vehicle) {
        throw new Exception('Orden de compra de vehículo no encontrada');
      }

      $vehicleMovement = VehicleMovement::create([
        'movement_type' => VehicleMovement::IN_TRANSIT,
        'ap_vehicle_id' => $vehicleId,
        'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
        'movement_date' => now(),
        'observation' => 'Vehículo en tránsito - Factura Dynamics: ' . $vehiclePurchaseOrder->invoice_dynamics,
        'previous_status_id' => $vehicle->ap_vehicle_status_id ?? ApVehicleStatus::PEDIDO_VN,
        'new_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
      ]);

      $vehicle->update([
        'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement when vehicle is added to inventory
   * @param $vehicleId
   * @return VehicleMovementResource
   * @throws Throwable
   */
  public function storeInventoryVehicleMovement($vehicleId): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehicle = Vehicles::find($vehicleId);
      if (!$vehicle) {
        throw new Exception('Vehículo no encontrado');
      }

      $vehicleMovement = VehicleMovement::create([
        'movement_type' => VehicleMovement::INVENTORY,
        'ap_vehicle_id' => $vehicleId,
        'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
        'movement_date' => now(),
        'observation' => 'Vehículo ingresado a inventario',
        'previous_status_id' => $vehicle->ap_vehicle_status_id ?? ApVehicleStatus::VEHICULO_EN_TRAVESIA,
        'new_status_id' => ApVehicleStatus::INVENTARIO_VN,
      ]);

      $warehouse = Warehouse::where('is_received', 1)
        ->where('article_class_id', $vehicle->warehouse->article_class_id)
        ->where('sede_id', $vehicle->warehouse->sede_id)
        ->where('type_operation_id', $vehicle->warehouse->type_operation_id)
        ->where('status', 1)->first();

      $vehicle->update([
        'ap_vehicle_status_id' => ApVehicleStatus::INVENTARIO_VN,
        'warehouse_id' => $warehouse ? $warehouse->id : throw new Exception('No se encontró un almacén válido para el vehículo'),
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement when a credit note is set (vehículo devuelto)
   * @throws Exception|Throwable
   */
  public function storeReturnedVehicleMovement($vehiclePurchaseOrderId, $creditNote = null): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehiclePurchaseOrder = PurchaseOrder::find($vehiclePurchaseOrderId);
      $vehicleId = $vehiclePurchaseOrder->vehicle->id;

      $vehicle = Vehicles::find($vehicleId);
      if (!$vehiclePurchaseOrder || !$vehicleId || !$vehicle) {
        throw new Exception('Orden de compra de vehículo no encontrada');
      }

      $vehicleMovement = VehicleMovement::create([
        'movement_type' => VehicleMovement::IN_TRANSIT_RETURNED,
        'ap_vehicle_id' => $vehicleId,
        'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO,
        'movement_date' => now(),
        'observation' => 'Vehículo devuelto por NC: ' . ($creditNote ?? ''),
        'previous_status_id' => $vehicle->ap_vehicle_status_id ?? ApVehicleStatus::VEHICULO_EN_TRAVESIA,
        'new_status_id' => ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO,
      ]);

      $vehicle->update([
        'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO,
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      Log::warning('Error al crear movimiento de vehículo devuelto: ' . $e->getMessage());
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement when a schedule delivery
   * @throws Exception|Throwable
   */
  public function storeScheduleDeliveryVehicleMovement(Vehicles $vehicle): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehicleMovement = VehicleMovement::create([
        'movement_type' => VehicleMovement::SOLD_NOT_DELIVERED,
        'ap_vehicle_id' => $vehicle->id,
        'ap_vehicle_status_id' => ApVehicleStatus::VENDIDO_NO_ENTREGADO,
        'movement_date' => now(),
        'observation' => 'Vehículo programado para entrega',
        'previous_status_id' => $vehicle->ap_vehicle_status_id ?? ApVehicleStatus::INVENTARIO_VN,
        'new_status_id' => ApVehicleStatus::VENDIDO_NO_ENTREGADO,
      ]);

      $vehicle->update([
        'ap_vehicle_status_id' => ApVehicleStatus::VENDIDO_NO_ENTREGADO,
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement when a schedule delivery
   * @throws Exception|Throwable
   */
  public function storeCompletedDeliveryVehicleMovement(Vehicles $vehicle, string $originAddress, string $destinationAddress): VehicleMovementResource
  {
    DB::beginTransaction();
    try {
      $vehicleMovement = VehicleMovement::create([
        'movement_type' => VehicleMovement::SOLD_DELIVERED,
        'ap_vehicle_id' => $vehicle->id,
        'ap_vehicle_status_id' => ApVehicleStatus::VENDIDO_ENTREGADO,
        'movement_date' => now(),
        'observation' => 'Vehículo entregado al cliente',
        'origin_address' => $originAddress,
        'destination_address' => $destinationAddress,
        'previous_status_id' => $vehicle->ap_vehicle_status_id ?? ApVehicleStatus::INVENTARIO_VN,
        'new_status_id' => ApVehicleStatus::VENDIDO_ENTREGADO,
      ]);

      $vehicle->update([
        'ap_vehicle_status_id' => ApVehicleStatus::VENDIDO_ENTREGADO,
      ]);

      DB::commit();
      return new VehicleMovementResource($vehicleMovement);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Create a vehicle movement for shipping guide (TRAVESIA)
   * @param int $vehicleId
   * @param string $originAddress
   * @param string $destinationAddress
   * @param string|null $observation
   * @param string|null $issueDate
   * @return VehicleMovement
   * @throws Exception
   */
  public function storeShippingGuideVehicleMovement(
    int     $vehicleId,
    string  $originAddress,
    string  $destinationAddress,
    ?string $observation = null,
    ?string $issueDate = null
  ): VehicleMovement
  {
    $vehicle = Vehicles::find($vehicleId);
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }

    $statusCurrentVehicle = $vehicle->ap_vehicle_status_id;

    $vehicleMovementData = [
      'ap_vehicle_id' => $vehicleId,
      'movement_type' => 'TRAVESIA',
      'movement_date' => $issueDate ?? now(),
      'observation' => $observation,
      'origin_address' => $originAddress,
      'destination_address' => $destinationAddress,
      'previous_status_id' => $statusCurrentVehicle,
      'new_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
      'ap_vehicle_status_id' => $statusCurrentVehicle,
      'created_by' => auth()->id(),
    ];

    $vehicle->update([
      'ap_vehicle_status_id' => ApVehicleStatus::VEHICULO_EN_TRAVESIA,
    ]);

    return VehicleMovement::create($vehicleMovementData);
  }

  /**
   * Create a vehicle movement for shipping guide in consignment (EN CONSIGNACION)
   * @param int $vehicleId
   * @param string $originAddress
   * @param string $destinationAddress
   * @param string|null $observation
   * @param string|null $issueDate
   * @return VehicleMovement
   * @throws Exception
   */
  public function storeShippingGuideConsignmentVehicleMovement(
    int     $vehicleId,
    string  $originAddress,
    string  $destinationAddress,
    ?string $observation = null,
    ?string $issueDate = null
  ): VehicleMovement
  {
    $vehicle = Vehicles::find($vehicleId);
    if (!$vehicle) {
      throw new Exception('Vehículo no encontrado');
    }

    $statusCurrentVehicle = $vehicle->ap_vehicle_status_id;

    $vehicleMovementData = [
      'ap_vehicle_id' => $vehicleId,
      'movement_type' => VehicleMovement::CONSIGNMENT,
      'movement_date' => $issueDate ?? now(),
      'observation' => $observation,
      'origin_address' => $originAddress,
      'destination_address' => $destinationAddress,
      'previous_status_id' => $statusCurrentVehicle,
      'new_status_id' => ApVehicleStatus::CONSIGNACION,
      'ap_vehicle_status_id' => ApVehicleStatus::CONSIGNACION,
      'created_by' => auth()->id(),
    ];

    $vehicle->update([
      'ap_vehicle_status_id' => ApVehicleStatus::CONSIGNACION,
    ]);

    return VehicleMovement::create($vehicleMovementData);
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
        $vehiclePurchaseOrder = PurchaseOrder::find($data['ap_vehicle_purchase_order_id']);
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
