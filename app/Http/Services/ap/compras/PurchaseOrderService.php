<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseOrderDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderItemDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderResource;
use App\Http\Services\ap\comercial\VehiclesService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\BaseService;
use App\Jobs\SyncCreditNoteDynamicsJob;
use App\Jobs\SyncInvoiceDynamicsJob;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Jobs\VerifyAndMigratePurchaseOrderJob;
use App\Jobs\VerifyAndMigrateShippingGuideJob;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseReception;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\ap\postventa\taller\ApSupplierOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseOrderService extends BaseService implements BaseServiceInterface
{
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
    $purchaseOrder = PurchaseOrder::with([
      'items',
      'supplier',
      'warehouse',
      'quotation',
    ])->where('id', $id)->first();
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
   * @param true $isCreate
   * @return mixed
   * @throws Exception
   */
  public function enrichData(mixed $data, bool $isCreate = true): mixed
  {
    if ($isCreate) {
      // Generar número de OC correlativo
      $series = AssignSalesSeries::where('sede_id', $data['sede_id'])
        ->where('type_operation_id', $data['type_operation_id'])
        ->where('type', AssignSalesSeries::PURCHASE)
        ->where('status', true)
        ->whereNull('deleted_at')
        ->first();

      if (!$series) throw new Exception('No hay una serie asignada para la sede y tipo de operación proporcionados');

      // Obtener el máximo correlativo real (incluyendo anuladas) para evitar duplicados
      $maxCorrelative = PurchaseOrder::where('sede_id', $data['sede_id'])
        ->where('type_operation_id', $data['type_operation_id'])
        ->where('status', true)
        ->whereNull('deleted_at')
        ->max('number_correlative');

      $number_correlative = $maxCorrelative ? $maxCorrelative + 1 : $series->correlative_start;

      // Si no es producción, sumar 1000 al correlativo para evitar conflictos
      if (config('app.env') !== 'production') {
        $number_correlative += 1000;
      }

      $number = $this->completeNumber($number_correlative, 7);

      $data['number_correlative'] = $number_correlative;
      $data['number'] = $series->series . $number;
      $data['number_guide'] = $series->series . $number;

      // Estado inicial de migración
      $data['migration_status'] = 'pending';
      $data['status'] = true;
    }

    // Obtener tipo de cambio actual si no viene en el request
    $exchangeRateService = new ExchangeRateService();
    $data['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;

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
    $data['payment_terms'] = $data['payment_terms'] ?? null;

    return $data;
  }

  /**
   * Obtiene el próximo número correlativo para una orden de compra
   * @param int $sedeId
   * @param int $typeOperationId
   * @return array
   * @throws Exception
   */
  public function nextCorrelative(int $sedeId, int $typeOperationId): array
  {
    $series = AssignSalesSeries::where('sede_id', $sedeId)
      ->where('type_operation_id', $typeOperationId)
      ->where('type', AssignSalesSeries::PURCHASE)
      ->where('status', true)
      ->whereNull('deleted_at')
      ->first();

    if (!$series) {
      throw new Exception('No hay una serie asignada para la sede y tipo de operación proporcionados');
    }

    $maxCorrelative = PurchaseOrder::where('sede_id', $sedeId)
      ->where('type_operation_id', $typeOperationId)
      ->where('status', true)
      ->whereNull('deleted_at')
      ->max('number_correlative');

    $numberCorrelative = $maxCorrelative ? $maxCorrelative + 1 : $series->correlative_start;
    $number = $series->series . $this->completeNumber($numberCorrelative, 7);

    return [
      'series' => 'OC' . $series->series,
      'number_correlative' => $numberCorrelative,
      'number' => 'OC' . $number,
    ];
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
   * Despacha un job de migración con deduplicación para evitar jobs duplicados
   * Usa cache de base de datos como lock para prevenir dispatch de jobs múltiples
   * para la misma orden de compra
   * @param int $purchaseOrderId
   * @return void
   */
  protected function dispatchJobWithDeduplication(int $purchaseOrderId): void
  {
    $cacheKey = "verify-po-{$purchaseOrderId}";

    // Verificar si ya hay un job activo para esta orden (lock existe)
    if (Cache::store('database')->has($cacheKey)) {
      // Ya hay un job activo, no despachar otro
      return;
    }

    // Marcar como activo (lock por 10 minutos = 600 segundos)
    // Este lock se limpiará automáticamente después de 10 minutos
    // Si el job termina antes, el lock persiste pero no afecta porque ya se procesó
    Cache::store('database')->put($cacheKey, true, 600);

    // Despachar job
    VerifyAndMigratePurchaseOrderJob::dispatch($purchaseOrderId);
  }

  /**
   * Crea una nueva orden de compra
   * Si tiene datos de vehículo, crea: Vehicle → VehicleMovement → PurchaseOrder
   * Si type_operation_id = TIPO_OPERACION_POSTVENTA, actualiza quantity_in_transit
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): PurchaseOrderResource
  {
    DB::beginTransaction();
    try {
      // Guardar items temporalmente
      $items = $data['items'] ?? [];

      if (isset($data['ap_supplier_order_id'])) {
        $supplierOrder = ApSupplierOrder::find($data['ap_supplier_order_id']);
        if ($supplierOrder && !is_null($supplierOrder->ap_purchase_order_id)) {
          throw new Exception("La orden al proveedor ya tiene una orden de compra registrada. No se puede crear otra.");
        }
      }

      // Lógica de consignación (ANTES de verificar vehículo en items)
      $isConsignment = isset($data['consignment_shipping_guide_id']);
      $consignmentGuide = null;
      if ($isConsignment) {
        $consignmentGuide = ShippingGuides::with('vehicleMovement.vehicle')->findOrFail($data['consignment_shipping_guide_id']);
        $consignmentVehicle = $consignmentGuide->vehicleMovement?->vehicle
          ?? throw new Exception('No hay vehículo asociado a la guía de consignación');

        if ($consignmentVehicle->ap_vehicle_status_id !== ApVehicleStatus::CONSIGNACION) {
          throw new Exception('El vehículo no está en estado CONSIGNACION');
        }

        $pedidoMovement = VehicleMovement::create([
          'movement_type' => VehicleMovement::ORDERED,
          'ap_vehicle_id' => $consignmentVehicle->id,
          'ap_vehicle_status_id' => $consignmentVehicle->ap_vehicle_status_id,
          'observation' => 'Orden de compra por vehículo en consignación',
          'movement_date' => now(),
          'previous_status_id' => ApVehicleStatus::CONSIGNACION,
          'new_status_id' => ApVehicleStatus::PEDIDO_VN,
          'created_by' => auth()->id(),
        ]);
        $consignmentVehicle->update(['ap_vehicle_status_id' => ApVehicleStatus::PEDIDO_VN]);

        $data['vehicle_movement_id'] = $pedidoMovement->id;
      }

      // Verificar si la orden incluye un vehículo
      $hasVehicle = !$isConsignment && $this->hasVehicleInItems($items);

      // Si tiene vehículo, crear el flujo completo: Vehicle → VehicleMovement
      if ($hasVehicle) {
        $vehicleMovementId = $this->createVehicleAndMovement($data);
        $data['vehicle_movement_id'] = $vehicleMovementId;
      }

      // Enriquecer datos de la orden
      $data = $this->enrichData($data);

      // Si type_operation_id = TIPO_OPERACION_POSTVENTA, validar que productos existan en almacén
      if (isset($data['type_operation_id']) && $data['type_operation_id'] == ApMasters::TIPO_OPERACION_POSTVENTA) {
        $this->validateProductsInWarehouse($items, $data['warehouse_id']);
      }

      // Crear la orden de compra
      $purchaseOrder = PurchaseOrder::create($data);

      // si ap_supplier_order_id actualizar su ap_purchase_order_id
      if (isset($data['ap_supplier_order_id'])) {
        $supplierOrder = ApSupplierOrder::find($data['ap_supplier_order_id']);
        if ($supplierOrder) {
          $supplierOrder->update([
            'ap_purchase_order_id' => $purchaseOrder->id,
          ]);
        }
      }

      // Guardar items si existen
      $this->saveItemsIfExists($items, $purchaseOrder);

      // Si viene purchase_reception_id, procesar la recepción y vincularla con la factura
      if (isset($data['purchase_reception_id'])) {
        $this->linkReceptionToInvoice($purchaseOrder, $data['purchase_reception_id']);
      }

      // Si type_operation_id = TIPO_OPERACION_POSTVENTA, actualizar quantity_in_transit
      if (isset($data['type_operation_id']) && $data['type_operation_id'] == ApMasters::TIPO_OPERACION_POSTVENTA) {
        $this->updateInTransitStockOnCreate($purchaseOrder);
      }

      // Para consignación: guardar dynamics_date y despachar migración de la guía
      if ($isConsignment && $consignmentGuide) {
        $consignmentGuide->update([
          'dynamics_date' => $purchaseOrder->emission_date,
          'migration_status' => 'pending',
        ]);
        if (config('database_sync.enabled', false)) {
          VerifyAndMigrateShippingGuideJob::dispatch($consignmentGuide->id)
            ->onQueue('shipping_guides');
        }
      }

      // Despachar job de migración y sincronización si está habilitado
      // Usa deduplicación para evitar jobs duplicados
      if (config('database_sync.enabled', false)) {
        $this->dispatchJobWithDeduplication($purchaseOrder->id);
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
      'type_operation_id' => ApMasters::TIPO_OPERACION_COMERCIAL,
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

      // Save old items for stock update (before deletion)
      $oldItems = $purchaseOrder->items->all();

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

        // Si type_operation_id = TIPO_OPERACION_POSTVENTA, actualizar quantity_in_transit
        if ($purchaseOrder->type_operation_id == ApMasters::TIPO_OPERACION_POSTVENTA) {
          $this->updateInTransitStockOnUpdate($purchaseOrder, $oldItems, $items);
        }
      }

      // Despachar job de migración si está habilitado y la orden está pendiente de migración
      // Usa deduplicación para evitar jobs duplicados en actualizaciones
      if (config('database_sync.enabled', false) && $purchaseOrder->migration_status !== 'completed') {
        $this->dispatchJobWithDeduplication($purchaseOrder->id);
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
      // Si type_operation_id = TIPO_OPERACION_POSTVENTA, remover quantity_in_transit antes de eliminar
      if ($purchaseOrder->type_operation_id == ApMasters::TIPO_OPERACION_POSTVENTA) {
        $this->removeInTransitStockOnDestroy($purchaseOrder);
      }

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
          'unit_measurement_id' => $itemData['unit_measurement_id'] ?? null,
          'description' => $itemData['description'] ?? '',
          'unit_price' => $unitPrice,
          'quantity' => $quantity,
          'quantity_received' => 0,
          'quantity_pending' => $quantity,
          'total' => $total,
          'is_vehicle' => $itemData['is_vehicle'] ?? false,
          'product_id' => $itemData['product_id'] ?? null,
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
      // Usa deduplicación para evitar jobs duplicados
      if (config('database_sync.enabled', false)) {
        $this->dispatchJobWithDeduplication($newPurchaseOrder->id);
      }

      DB::commit();

      return new PurchaseOrderResource($newPurchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update quantity_in_transit when creating a purchase order with type_operation_id = TIPO_OPERACION_POSTVENTA
   * @param PurchaseOrder $purchaseOrder
   * @return void
   * @throws Exception
   */
  protected function updateInTransitStockOnCreate(PurchaseOrder $purchaseOrder): void
  {
    $stockService = new ProductWarehouseStockService();

    foreach ($purchaseOrder->items as $item) {
      // Only process items with product_id
      if ($item->product_id) {
        $stockService->addInTransitStock(
          $item->product_id,
          $purchaseOrder->warehouse_id,
          $item->quantity
        );
      }
    }
  }

  /**
   * Update quantity_in_transit when updating a purchase order with type_operation_id = TIPO_OPERACION_POSTVENTA
   * @param PurchaseOrder $purchaseOrder
   * @param array $oldItems
   * @param array $newItems
   * @return void
   * @throws Exception
   */
  protected function updateInTransitStockOnUpdate(PurchaseOrder $purchaseOrder, array $oldItems, array $newItems): void
  {
    $stockService = new ProductWarehouseStockService();

    // Create a map of old items by product_id
    $oldItemsMap = [];
    foreach ($oldItems as $oldItem) {
      if ($oldItem->product_id) {
        $oldItemsMap[$oldItem->product_id] = $oldItem->quantity;
      }
    }

    // Create a map of new items by product_id
    $newItemsMap = [];
    foreach ($newItems as $newItem) {
      if (isset($newItem['product_id']) && $newItem['product_id']) {
        $newItemsMap[$newItem['product_id']] = $newItem['quantity'];
      }
    }

    // Update stock for each product
    $allProductIds = array_unique(array_merge(array_keys($oldItemsMap), array_keys($newItemsMap)));

    foreach ($allProductIds as $productId) {
      $oldQuantity = $oldItemsMap[$productId] ?? 0;
      $newQuantity = $newItemsMap[$productId] ?? 0;

      if ($oldQuantity != $newQuantity) {
        $stockService->updateInTransitStock(
          $productId,
          $purchaseOrder->warehouse_id,
          $oldQuantity,
          $newQuantity
        );
      }
    }
  }

  /**
   * Remove quantity_in_transit when destroying a purchase order with type_operation_id = TIPO_OPERACION_POSTVENTA
   * @param PurchaseOrder $purchaseOrder
   * @return void
   * @throws Exception
   */
  protected function removeInTransitStockOnDestroy(PurchaseOrder $purchaseOrder): void
  {
    $stockService = new ProductWarehouseStockService();

    foreach ($purchaseOrder->items as $item) {
      // Only process items with product_id
      if ($item->product_id) {
        $stockService->removeInTransitStock(
          $item->product_id,
          $purchaseOrder->warehouse_id,
          $item->quantity
        );
      }
    }
  }

  /**
   * Validate that products exist in warehouse stock before creating purchase order
   * Only for TIPO_OPERACION_POSTVENTA orders
   * @param array $items
   * @param int $warehouseId
   * @return void
   * @throws Exception
   */
  protected function validateProductsInWarehouse(array $items, int $warehouseId): void
  {
    $stockService = new ProductWarehouseStockService();
    $missingProducts = [];
    $warehouse = Warehouse::find($warehouseId);
    $warehouseName = $warehouse ? $warehouse->description : "ID: {$warehouseId}";

    foreach ($items as $item) {
      // Only validate items with product_id (skip vehicles or items without product)
      if (isset($item['product_id']) && $item['product_id']) {
        $stock = $stockService->getStock($item['product_id'], $warehouseId);

        if (!$stock) {
          $product = Products::find($item['product_id']);
          $productName = $product ? $product->name : "ID: {$item['product_id']}";
          $missingProducts[] = $productName;
        }
      }
    }

    if (!empty($missingProducts)) {
      throw new Exception(
        'Los siguientes productos no están asignados al almacén "' . $warehouseName . '": ' .
        implode(', ', $missingProducts) . '. ' .
        'Por favor, registre estos productos en el almacén antes de continuar.'
      );
    }
  }

  protected function linkReceptionToInvoice(PurchaseOrder $purchaseOrder, int $receptionId): void
  {
    $reception = PurchaseReception::find($receptionId);
    if (!$reception) {
      throw new Exception("Recepción ID {$receptionId} no encontrada");
    }

    if ($reception->purchase_order_id && $reception->purchase_order_id !==
      $purchaseOrder->id) {
      throw new Exception("La recepción ya está vinculada a otra orden de compra");
    }

    // SOLO vincular
    $reception->update(['purchase_order_id' => $purchaseOrder->id]);

    // Vincular detalles con items
    foreach ($reception->details as $receptionDetail) {
      $orderItem = $purchaseOrder->items()
        ->where('product_id', $receptionDetail->product_id)
        ->first();

      if ($orderItem) {
        $receptionDetail->update(['purchase_order_item_id' =>
          $orderItem->id]);
      }
    }
  }

  public function checkResources($id)
  {
    $purchaseOrder = $this->find($id);

    return [
      'header' => new PurchaseOrderDynamicsResource($purchaseOrder),
      'detail' => new PurchaseOrderItemDynamicsResource($purchaseOrder->items),
    ];
  }

  /**
   * Despacha un job para sincronizar la nota de crédito de una orden de compra con Dynamics
   * @param $id
   * @return string[]
   * @throws Exception
   */
  public function dispatchSyncCreditNoteJob($id): array
  {
    $purchaseOrder = $this->find($id);
    SyncCreditNoteDynamicsJob::dispatchSync($purchaseOrder->id);
    return [
      'message' => "Job de sincronización de nota de crédito para la orden de compra {$purchaseOrder->number} ha sido despachado."
    ];
  }

  /**
   * Despacha un job para sincronizar la factura de una orden de compra con Dynamics
   * @param $id
   * @return string[]
   * @throws Exception
   */
  public function dispatchSyncInvoiceJob($id): array
  {
    $purchaseOrder = $this->find($id);
    SyncInvoiceDynamicsJob::dispatchSync($purchaseOrder->id);
    return [
      'message' => "Job de sincronización de factura para la orden de compra {$purchaseOrder->number} ha sido despachado."
    ];
  }
}
