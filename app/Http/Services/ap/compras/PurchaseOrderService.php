<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseOrderResource;
use App\Http\Services\ap\comercial\VehiclesService;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Http\Utils\Constants;
use App\Jobs\VerifyAndMigratePurchaseOrderJob;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseOrderService extends BaseService implements BaseServiceInterface
{
  protected int $startNumber = 2;
  protected ExportService $exportService;

  public function __construct(
    ExportService $exportService
  )
  {
    $this->exportService = $exportService;
  }

  /**
   * Exporta las órdenes de compra según los filtros proporcionados
   * @param Request $request
   * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, PurchaseOrder::class);
  }

  /**
   * Lista las órdenes de compra con filtros y paginación
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseOrder::class,
      $request,
      PurchaseOrder::filters,
      PurchaseOrder::sorts,
      PurchaseOrderResource::class
    );
  }

  /**
   * Busca una orden de compra por ID
   * @param $id
   * @return mixed
   * @throws Exception
   */
  public function find($id)
  {
    $purchaseOrder = PurchaseOrder::with(['items', 'supplier', 'warehouse'])->where('id', $id)->first();
    if (!$purchaseOrder) {
      throw new Exception('Orden de compra no encontrada');
    }
    return $purchaseOrder;
  }

  /**
   * Enriquece los datos de la orden de compra antes de crear o actualizar
   * Solo genera números correlativos y establece valores por defecto
   * Los valores de la factura (subtotal, igv, total, etc.) vienen del request
   * @param mixed $data
   * @param $isCreate
   * @return mixed
   * @throws Exception
   */
  public function enrichData(mixed $data, $isCreate = true)
  {
    if ($isCreate) {
      // Generar número de OC correlativo
      $series = $this->completeSeries(Sede::find($data['sede_id'])->id);

      $number_correlative = $this->nextCorrelativeQueryInteger(PurchaseOrder::where('status', true), 'number_correlative') + $this->startNumber;
      $number = $this->completeNumber($number_correlative);

      $data['number_correlative'] = $number_correlative;
      $data['number'] = $series . $number;
      $data['number_guide'] = $series . $number;

      // Estado inicial de migración
      $data['migration_status'] = 'pending';
      $data['status'] = true;
    }

    // Obtener tipo de cambio actual si no viene en el request
    if (!isset($data['exchange_rate_id'])) {
      $exchangeRateService = new ExchangeRateService();
      $data['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;
    }

    // Validar que los valores requeridos de la factura estén presentes
    $requiredFields = ['subtotal', 'igv', 'total'];
    foreach ($requiredFields as $field) {
      if (!isset($data[$field])) {
        throw new Exception("El campo '{$field}' es requerido");
      }
    }

    // Establecer valores por defecto para campos opcionales
    $data['discount'] = $data['discount'] ?? 0;
    $data['isc'] = $data['isc'] ?? 0;

    return $data;
  }

  public function hasVehicleInItems(array $items): bool
  {
    foreach ($items as $item) {
      if (isset($item['is_vehicle']) && $item['is_vehicle'] === true) {
        return true;
      }
    }
    return false;
  }

  /**
   * Crea una nueva orden de compra
   * Si tiene datos de vehículo, crea: Vehicle → VehicleMovement → PurchaseOrder
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): PurchaseOrderResource
  {
    DB::beginTransaction();
    try {
      // Guardar items temporalmente
      $items = $data['items'] ?? [];

      // Verificar si la orden incluye un vehículo
      $hasVehicle = $this->hasVehicleInItems($items);

      // Si tiene vehículo, crear el flujo completo: Vehicle → VehicleMovement
      if ($hasVehicle) {
        $vehicleMovementId = $this->createVehicleAndMovement($data);
        $data['vehicle_movement_id'] = $vehicleMovementId;
      }

      // Enriquecer datos de la orden
      $data = $this->enrichData($data);

      // Crear la orden de compra
      $purchaseOrder = PurchaseOrder::create($data);

      // Guardar items si existen
      $this->saveItemsIfExists($items, $purchaseOrder);

      // Despachar job de migración y sincronización si está habilitado
      if (config('database_sync.enabled', false)) {
        VerifyAndMigratePurchaseOrderJob::dispatch($purchaseOrder->id);
      }

      DB::commit();
      return new PurchaseOrderResource($purchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Crea el vehículo y su movimiento inicial
   * @param array $data
   * @return int ID del VehicleMovement creado
   * @throws Exception
   */
  protected function createVehicleAndMovement(array $data): int
  {
    // 1. Crear el vehículo
    $vehicleService = new VehiclesService();
    $vehicleData = [
      'vin' => $data['vin'],
      'year' => $data['year'],
      'engine_number' => $data['engine_number'],
      'warehouse_id' => $data['warehouse_id'],
      'ap_models_vn_id' => $data['ap_models_vn_id'],
      'vehicle_color_id' => $data['vehicle_color_id'],
      'engine_type_id' => $data['engine_type_id'],
      'sede_id' => $data['sede_id'],
      'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN,
      'type_operation_id' => Constants::TYPE_OPERATION_COMERCIAL_ID,
    ];

    $vehicle = $vehicleService->store($vehicleData);

    // 2. Crear el movimiento del vehículo
    $vehicleMovement = VehicleMovement::create([
      'movement_type' => VehicleMovement::ORDERED,
      'ap_vehicle_id' => $vehicle->id,
      'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN,
      'observation' => 'Creación de orden de compra con vehículo',
      'movement_date' => now(),
      'previous_status_id' => null,
      'new_status_id' => ApVehicleStatus::PEDIDO_VN,
      'created_by' => auth()->id(),
    ]);

    return $vehicleMovement->id;
  }


  protected function createResendVehicleMovement($vehicleMovement, $numberOC): int
  {
    $vehicleMovement = VehicleMovement::create([
      'movement_type' => VehicleMovement::ORDERED,
      'ap_vehicle_id' => $vehicleMovement->ap_vehicle_id,
      'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN,
      'observation' => "Reenvío de orden de compra {$numberOC}",
      'movement_date' => now(),
      'previous_status_id' => $vehicleMovement->new_status_id,
      'new_status_id' => ApVehicleStatus::VEHICULO_TRANSITO_DEVUELTO,
      'created_by' => auth()->id(),
    ]);

    return $vehicleMovement->id;
  }

  /**
   * Sincroniza la orden de compra con Dynamics
   * @throws Exception
   * @deprecated Usar VerifyAndMigratePurchaseOrderJob en su lugar
   */
  // protected function syncPurchaseOrderToDynamics(PurchaseOrder $purchaseOrder): void
  // {
  //   $syncService = new DatabaseSyncService();
  //
  //   // Validar que existan items
  //   $purchaseOrder->load('items');
  //   if ($purchaseOrder->items->isEmpty()) {
  //     throw new Exception("La orden de compra debe tener al menos un ítem");
  //   }
  //
  //   // Sincronizar cabecera de la orden
  //   $headerResource = new PurchaseOrderDynamicsResource($purchaseOrder);
  //   $headerData = $headerResource->toArray(request());
  //   $syncService->sync('ap_purchase_order', $headerData);
  //
  //   // Sincronizar items de la orden
  //   $itemsResource = new PurchaseOrderItemDynamicsResource($purchaseOrder->items);
  //   $itemsData = $itemsResource->toArray(request());
  //
  //   // Los items vienen como un array, cada uno debe sincronizarse
  //   foreach ($itemsData as $itemData) {
  //     $syncService->sync('ap_purchase_order_item', $itemData);
  //   }
  // }

  /**
   * Muestra una orden de compra
   * @param $id
   * @return PurchaseOrderResource
   * @throws Exception
   */
  public function show($id)
  {
    return new PurchaseOrderResource($this->find($id));
  }

  /**
   * Actualiza una orden de compra
   * No recalcula valores de factura, estos deben venir en el request
   * @param mixed $data
   * @return PurchaseOrderResource
   * @throws Throwable
   */
  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $purchaseOrder = $this->find($data['id']);

      // Guardar items temporalmente
      $items = $data['items'] ?? null;

      // Validar datos si vienen valores de factura (sin recalcular)
      if (isset($data['subtotal']) || isset($data['igv']) || isset($data['total'])) {
        $data = $this->enrichData($data, false);
      }

      // Actualizar la orden
      $purchaseOrder->update($data);

      // Actualizar items si se proporcionaron
      if ($items !== null) {
        // Eliminar items existentes
        $purchaseOrder->items()->delete();

        // Crear nuevos items
        $this->saveItemsIfExists($items, $purchaseOrder);
      }

      // Despachar job de migración si está habilitado y la orden está pendiente de migración
      if (config('database_sync.enabled', false) && $purchaseOrder->migration_status !== 'completed') {
        VerifyAndMigratePurchaseOrderJob::dispatch($purchaseOrder->id);
      }

      DB::commit();
      return new PurchaseOrderResource($purchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina una orden de compra
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   * @throws Throwable
   */
  public function destroy($id)
  {
    $purchaseOrder = $this->find($id);
    DB::transaction(function () use ($purchaseOrder) {
      // Eliminar items primero
      $purchaseOrder->items()->delete();
      // Eliminar la orden
      $purchaseOrder->delete();
    });
    return response()->json(['message' => 'Orden de compra eliminada correctamente']);
  }

  /**
   * @param mixed $items
   * @param $purchaseOrder
   * @return void
   */
  public function saveItemsIfExists(mixed $items, $purchaseOrder): void
  {
    if (!empty($items)) {
      foreach ($items as $itemData) {
        $unitPrice = round($itemData['unit_price'], 2);
        $quantity = $itemData['quantity'] ?? 1;
        $total = round($unitPrice * $quantity, 2);

        $purchaseOrder->items()->create([
          'unit_measurement_id' => $itemData['unit_measurement_id'],
          'description' => $itemData['description'],
          'unit_price' => $unitPrice,
          'quantity' => $quantity,
          'total' => $total,
          'is_vehicle' => $itemData['is_vehicle'] ?? false,
        ]);
      }
    }
  }

  /**
   * Reenvía una orden de compra anulada creando una nueva con punto (.)
   * Solo se puede reenviar si tiene nota de crédito y está anulada
   * @param mixed $data Datos del request incluyendo items, valores de factura y datos del vehículo
   * @param int $originalId ID de la OC original a reenviar
   * @return PurchaseOrderResource
   * @throws Exception|Throwable
   */
  public function resend(mixed $data, $originalId): PurchaseOrderResource
  {
    DB::beginTransaction();
    try {
      $originalPO = $this->find($originalId);

      // Validar que la OC original tenga NC y esté anulada
      if (empty($originalPO->credit_note_dynamics)) {
        throw new Exception("La orden de compra {$originalPO->number} no tiene nota de crédito. No puede ser reenviada.");
      }

      if ($originalPO->status !== false) {
        throw new Exception("La orden de compra {$originalPO->number} no está anulada. No puede ser reenviada.");
      }

      // Validar que no haya sido reenviada previamente
      $alreadyResent = PurchaseOrder::where('original_purchase_order_id', $originalPO->id)
        ->exists();

      if ($alreadyResent) {
        throw new Exception("La orden de compra {$originalPO->number} ya ha sido reenviada previamente. No se puede reenviar nuevamente.");
      }

      // Marcar la OC original como reenviada
      $originalPO->update(['resent' => true]);

      // Guardar items del request temporalmente
      $items = $data['items'] ?? [];

      // Verificar si la orden incluye un vehículo
      $hasVehicle = $this->hasVehicleInItems($items);

      if ($hasVehicle) {
        $vehicle = $originalPO->vehicle;
        if (!$vehicle) {
          throw new Exception("La orden de compra original no tiene un vehículo asociado. No se puede reenviar con datos de vehículo.");
        }
        $vehicleMovement = $originalPO->vehicleMovement;
        if (!$vehicleMovement) {
          throw new Exception("La orden de compra original no tiene un movimiento de vehículo asociado. No se puede reenviar con datos de vehículo.");
        }
        $vehicleMovementId = $this->createResendVehicleMovement($vehicleMovement, $originalPO->number);
        $data['vehicle_movement_id'] = $vehicleMovementId;
      }

      // Preparar datos para la nueva OC basándose en la original y el request
      $newPOData = $data;

      // Agregar punto (.) al número y guía
      $newPOData['number'] = $originalPO->number . '.';
      $newPOData['number_guide'] = $originalPO->number_guide . '.';
      $newPOData['number_correlative'] = $originalPO->number_correlative;

      // Establecer relación con la OC original
      $newPOData['original_purchase_order_id'] = $originalPO->id;

      // Estado inicial de migración
      $newPOData['migration_status'] = 'pending';

      // No copiar campos de NC de la original
      $newPOData['credit_note_dynamics'] = null;

      // Asegurar que la nueva OC esté activa
      $newPOData['status'] = true;

      // Enriquecer datos (valida campos requeridos y establece defaults)
      $newPOData = $this->enrichData($newPOData, false);

      // Crear la nueva OC
      $newPurchaseOrder = PurchaseOrder::create($newPOData);

      // Guardar items del request (no copiar de la original)
      $this->saveItemsIfExists($items, $newPurchaseOrder);

      // Si tiene vehículo asociado, actualizar estado
      if ($hasVehicle) {
        $vehicleMovement->vehicle->update([
          'ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN
        ]);
      }

      // Despachar job de migración si está habilitado
      if (config('database_sync.enabled', false)) {
        VerifyAndMigratePurchaseOrderJob::dispatch($newPurchaseOrder->id);
      }

      DB::commit();

      return new PurchaseOrderResource($newPurchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

}
