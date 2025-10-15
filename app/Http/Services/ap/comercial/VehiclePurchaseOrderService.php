<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\DatabaseSyncService;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class VehiclePurchaseOrderService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      VehiclePurchaseOrder::class,
      $request,
      VehiclePurchaseOrder::filters,
      VehiclePurchaseOrder::sorts,
      VehiclePurchaseOrderResource::class
    );
  }

  public function find($id)
  {
    $vehiclePurchaseOrder = VehiclePurchaseOrder::where('id', $id)->first();
    if (!$vehiclePurchaseOrder) {
      throw new Exception('Orden de compra de vehículo no encontrada');
    }
    return $vehiclePurchaseOrder;
  }

  public function enrichData(mixed $data, $isCreate = true)
  {
    if ($isCreate) {
      $data['number'] =
        $this->nextCorrelativeQuery(Sede::where('id', $data['sede_id']), 'id', 2) .
        $this->nextCorrelativeCount(VehiclePurchaseOrder::class, 8, ['sede_id' => $data['sede_id'], 'status' => true]);

      $data['number_guide'] =
        $this->nextCorrelativeQuery(Sede::where('id', $data['sede_id']), 'id', 2) .
        $this->nextCorrelativeCount(VehiclePurchaseOrder::class, 8, ['sede_id' => $data['sede_id'], 'status' => true]);

      $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
      $exchangeRateService = new ExchangeRateService();
      $data['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;
    }

    $unit_price = round($data['unit_price'], 2);
    $discount = round($data['discount'], 2);
    $subtotal = round($unit_price - $discount, 2);
    if ($subtotal < 0) {
      throw new Exception('El subtotal no puede ser negativo');
    }
    $igv = round($subtotal * 0.18, 2);
    $total = round($subtotal + $igv, 2);

    $data['unit_price'] = $unit_price;
    $data['discount'] = $discount;
    $data['igv'] = $igv;
    $data['total'] = $total;
    $data['subtotal'] = $subtotal;

    return $data;
  }

  /**
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): VehiclePurchaseOrderResource
  {
    DB::beginTransaction();
    try {
      $data = $this->enrichData($data);

      // Establecer estado de migración inicial
      $data['migration_status'] = 'pending';

      $vehiclePurchaseOrder = VehiclePurchaseOrder::create($data);
      $vehicleMovementService = new VehicleMovementService();
      $vehicleMovementService->storeRequestedVehicleMovement($vehiclePurchaseOrder->id);

      // Crear logs iniciales de migración
      $this->createInitialMigrationLogs($vehiclePurchaseOrder);

      // Validar y sincronizar antes de enviar la OC
      $this->validateAndSyncBeforeSending($vehiclePurchaseOrder);

      // Enviar la orden de compra
      $this->syncPurchaseOrder($vehiclePurchaseOrder);

      // Despachar job de verificación y migración
      \App\Jobs\VerifyAndMigratePurchaseOrderJob::dispatch($vehiclePurchaseOrder->id)
        ->delay(now()->addSeconds(30)); // Esperar 30 segundos antes de verificar

      DB::commit();
      return new VehiclePurchaseOrderResource($vehiclePurchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Crea los logs iniciales de migración para todos los pasos
   */
  protected function createInitialMigrationLogs(VehiclePurchaseOrder $purchaseOrder): void
  {
    $supplier = $purchaseOrder->supplier;
    $model = $purchaseOrder->model;

    $steps = [
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER],
        'external_id' => $supplier?->num_doc,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER_ADDRESS,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER_ADDRESS],
        'external_id' => $supplier?->num_doc,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_ARTICLE,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ARTICLE],
        'external_id' => $model?->code,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER],
        'external_id' => $purchaseOrder->number,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL],
        'external_id' => $purchaseOrder->number,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_RECEPTION,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION],
        'external_id' => $purchaseOrder->number_guide,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL],
        'external_id' => $purchaseOrder->number_guide,
      ],
      [
        'step' => VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL,
        'table_name' => VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL],
        'external_id' => $purchaseOrder->vin,
      ],
    ];

    foreach ($steps as $stepData) {
      VehiclePurchaseOrderMigrationLog::create([
        'vehicle_purchase_order_id' => $purchaseOrder->id,
        'step' => $stepData['step'],
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $stepData['table_name'],
        'external_id' => $stepData['external_id'],
      ]);
    }
  }

  /**
   * Valida y sincroniza los datos antes de enviar la OC
   * @throws Exception
   */
  protected function validateAndSyncBeforeSending(VehiclePurchaseOrder $purchaseOrder): void
  {
    $syncService = new DatabaseSyncService();
    $resource = new VehiclePurchaseOrderResource($purchaseOrder);
    $resourceData = $resource->toArray(request());

    // 1. Validar que no existe la OC
    $existingPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $purchaseOrder->number)
      ->first();

    if ($existingPO) {
      throw new Exception("La orden de compra {$purchaseOrder->number} ya existe en el sistema de destino");
    }

    // 2. Validar y sincronizar el proveedor
    $this->validateAndSyncSupplier($purchaseOrder, $syncService);

    // 3. Validar y sincronizar el artículo
    $this->validateAndSyncArticle($purchaseOrder, $syncService);
  }

  /**
   * Valida y sincroniza el proveedor
   * @throws Exception
   */
  protected function validateAndSyncSupplier(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $supplier = $purchaseOrder->supplier;

    if (!$supplier) {
      throw new Exception("No se encontró el proveedor asociado a la orden de compra");
    }

    // 2. Validar que existe el proveedor en la BD intermedia
    $existingSupplier = DB::connection('dbtp')
      ->table('neInTbProveedor')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('NumeroDocumento', $supplier->num_doc)
      ->first();

    // 2.1. Si no existe, enviar el proveedor Y su dirección juntos
    if (!$existingSupplier) {
      $syncService->sync('business_partners_ap_supplier', $supplier->toArray(), 'create');
      $syncService->sync('business_partners_directions_ap_supplier', $supplier->toArray(), 'create');
    } else {
      // Si existe pero tiene error, notificar
      if (!empty($existingSupplier->ProcesoError)) {
        \Illuminate\Support\Facades\Log::warning("El proveedor {$supplier->num_doc} tiene un error previo: {$existingSupplier->ProcesoError}");
      }
    }
  }

  /**
   * Valida y sincroniza el artículo (modelo)
   * @throws Exception
   */
  protected function validateAndSyncArticle(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $model = $purchaseOrder->model;

    if (!$model) {
      throw new Exception("No se encontró el modelo asociado a la orden de compra");
    }

    // 3. Validar que existe el artículo en la BD intermedia
    $existingArticle = DB::connection('dbtp')
      ->table('neInTbArticulo')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('Articulo', $model->code)
      ->first();

    // 3.1. Si no existe, enviar el artículo mediante job
    if (!$existingArticle) {
      \App\Jobs\SyncArticleJob::dispatch($model->id);
    } else {
      // Si existe pero tiene error, notificar
      if (!empty($existingArticle->ProcesoError)) {
        \Illuminate\Support\Facades\Log::warning("El artículo {$model->code} tiene un error previo: {$existingArticle->ProcesoError}");
      }
    }
  }

  /**
   * Sincroniza la orden de compra
   * La recepción se sincronizará automáticamente mediante VerifyAndMigratePurchaseOrderJob
   * cuando la OC esté procesada (ProcesoEstado = 1)
   * @throws Exception
   */
  protected function syncPurchaseOrder(VehiclePurchaseOrder $purchaseOrder): void
  {
    $syncService = new DatabaseSyncService();
    $resource = new VehiclePurchaseOrderResource($purchaseOrder);
    $resourceData = $resource->toArray(request());

    // Enviar la OC Y su detalle juntos a la tabla intermedia
    $syncService->sync('ap_vehicle_purchase_order', $resourceData, 'create');
    $syncService->sync('ap_vehicle_purchase_order_det', $resourceData, 'create');

    // NOTA: No despachamos SyncPurchaseOrderReceptionJob aquí porque crearía una condición de carrera.
    // El job VerifyAndMigratePurchaseOrderJob (despachado en store/update/resend) se encarga de:
    // 1. Esperar a que la OC sea procesada (ProcesoEstado = 1)
    // 2. Sincronizar la recepción (NI) solo cuando la OC esté lista
  }

  public function show($id)
  {
    return new VehiclePurchaseOrderResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $vehiclePurchaseOrder = $this->find($data['id']);

      // Si la OC tiene NC, crear una nueva OC con punto en vez de actualizar
      if (!empty($vehiclePurchaseOrder->credit_note_dynamics)) {
        Log::info("OC {$vehiclePurchaseOrder->id} tiene NC, creando nueva OC con punto");

        // Crear nueva OC basada en los datos actualizados
        $newPOData = array_merge($vehiclePurchaseOrder->toArray(), $data);
        unset($newPOData['id']); // Quitar el ID para crear nuevo registro
        unset($newPOData['created_at']);
        unset($newPOData['updated_at']);
        unset($newPOData['deleted_at']);

        // Agregar punto al número y guía automáticamente
        $newPOData['number'] = $vehiclePurchaseOrder->number . '.';
        $newPOData['number_guide'] = $vehiclePurchaseOrder->number_guide . '.';
        $newPOData['migration_status'] = 'pending';
        $newPOData['original_purchase_order_id'] = $vehiclePurchaseOrder->id; // CLAVE: Vincular con la original

        // Recalcular precios si fueron modificados
        if (isset($data['unit_price']) || isset($data['discount'])) {
          if (!isset($data['unit_price'])) {
            $newPOData['unit_price'] = $vehiclePurchaseOrder->unit_price;
          }
          if (!isset($data['discount'])) {
            $newPOData['discount'] = $vehiclePurchaseOrder->discount;
          }
          $newPOData = $this->enrichData($newPOData, false);
        }

        // Crear la nueva OC
        $newPurchaseOrder = VehiclePurchaseOrder::create($newPOData);

        // Crear movimiento para la nueva OC
        $vehicleMovementService = new VehicleMovementService();
        $vehicleMovementService->storeRequestedVehicleMovement($newPurchaseOrder->id);

        // Crear logs de migración
        $this->createInitialMigrationLogs($newPurchaseOrder);

        Log::info("Nueva OC creada con punto: {$newPurchaseOrder->number} (ID: {$newPurchaseOrder->id})");

        // Sincronizar la nueva OC (que actualizará la intermedia en vez de insertar)
        $this->validateAndSyncBeforeSending($newPurchaseOrder);
        $this->syncPurchaseOrder($newPurchaseOrder);

        // Despachar job de verificación
        \App\Jobs\VerifyAndMigratePurchaseOrderJob::dispatch($newPurchaseOrder->id)
          ->delay(now()->addSeconds(30));

        DB::commit();
        return new VehiclePurchaseOrderResource($newPurchaseOrder);
      }

      // Flujo normal: actualizar OC sin NC
      if (isset($data['unit_price']) || isset($data['discount'])) {
        if (!isset($data['unit_price'])) {
          $data['unit_price'] = $vehiclePurchaseOrder->unit_price;
        }
        if (!isset($data['discount'])) {
          $data['discount'] = $vehiclePurchaseOrder->discount;
        }
        $data = $this->enrichData($data, false);
      }

      $vehiclePurchaseOrder->update($data);


      DB::commit();
      return new VehiclePurchaseOrderResource($vehiclePurchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $vehiclePurchaseOrder = $this->find($id);
    DB::transaction(function () use ($vehiclePurchaseOrder) {
      $vehiclePurchaseOrder->delete();
    });
    return response()->json(['message' => 'Orden de compra de vehículo eliminada correctamente']);
  }

  /**
   * Reenvía una OC anulada (con NC) con datos corregidos
   * Crea nueva OC con punto (.) al final del número
   *
   * @throws Exception
   * @throws Throwable
   */
  public function resend(mixed $data, $originalId): VehiclePurchaseOrderResource
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
      $alreadyResent = VehiclePurchaseOrder::where('original_purchase_order_id', $originalPO->id)
        ->exists();

      if ($alreadyResent) {
        throw new Exception("La orden de compra {$originalPO->number} ya ha sido reenviada previamente. No se puede reenviar nuevamente.");
      }

      Log::info("Reenviando OC anulada {$originalPO->number} con datos corregidos");

      // Marcar la OC original como reenviada
      $originalPO->update(['resent' => true]);

      // Preparar datos para la nueva OC
      $newPOData = $data;

      // Agregar punto (.) al número y guía
      $newPOData['number'] = $originalPO->number . '.';
      $newPOData['number_guide'] = $originalPO->number_guide . '.';

      // Establecer relación con la OC original
      $newPOData['original_purchase_order_id'] = $originalPO->id;

      // Estado inicial de migración
      $newPOData['migration_status'] = 'pending';

      // Asegurar que la nueva OC esté activa
      $newPOData['status'] = true;

      // No copiar campos de NC de la original
      $newPOData['credit_note_dynamics'] = null;

      // Calcular campos que normalmente se calculan en enrichData cuando $isCreate = true
      // porque estamos creando una nueva OC
      $newPOData['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
      $exchangeRateService = new ExchangeRateService();
      $newPOData['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;

      // Enriquecer datos (calcular precios)
      $newPOData = $this->enrichData($newPOData, false);

      // Crear la nueva OC
      $newPurchaseOrder = VehiclePurchaseOrder::create($newPOData);

      // Crear movimiento para la nueva OC
      $vehicleMovementService = new VehicleMovementService();
      $vehicleMovementService->storeRequestedVehicleMovement($newPurchaseOrder->id);

      // Crear logs de migración
      $this->createInitialMigrationLogs($newPurchaseOrder);

      Log::info("Nueva OC creada con punto: {$newPurchaseOrder->number} (ID: {$newPurchaseOrder->id})");

      // Validar y sincronizar a tabla intermedia
      $this->validateAndSyncBeforeSending($newPurchaseOrder);
      $this->syncPurchaseOrder($newPurchaseOrder);

      // Despachar job de verificación y migración
      \App\Jobs\VerifyAndMigratePurchaseOrderJob::dispatch($newPurchaseOrder->id)
        ->delay(now()->addSeconds(30));

      DB::commit();

      Log::info("OC {$newPurchaseOrder->number} reenviada exitosamente. Original: {$originalPO->number}");

      return new VehiclePurchaseOrderResource($newPurchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error("Error al reenviar OC {$originalId}: {$e->getMessage()}");
      throw $e;
    }
  }
}
