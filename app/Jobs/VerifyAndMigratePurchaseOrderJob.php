<?php

namespace App\Jobs;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderDetailDynamicsResource;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderItemDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderVehicleReceptionResource;
use App\Http\Resources\ap\compras\PurchaseOrderVehicleReceptionDetailResource;
use App\Http\Resources\ap\compras\PurchaseOrderVehicleReceptionSerialResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyAndMigratePurchaseOrderJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 2; // Reducido de 5 → 2 para evitar crecimiento exponencial de jobs
  public int $timeout = 300;
  public int $backoff = 120; // Aumentado a 120 segundos para dar más tiempo entre reintentos

  /**
   * Create a new job instance.
   */
  public function __construct(
    public ?int $purchaseOrderId = null
  )
  {
    $this->onQueue('purchase_orders');
  }

  /**
   * Execute the job.
   * Si se proporciona un ID, procesa solo esa OC
   * Si no, procesa todas las OCs no migradas
   * @throws Exception
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      if ($this->purchaseOrderId) {
        $this->processPurchaseOrder($this->purchaseOrderId, $syncService);
      } else {
        $this->processAllPendingPurchaseOrders($syncService);
      }
    } catch (Exception $e) {
      Log::error('Error en VerifyAndMigratePurchaseOrderJob: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Procesa todas las órdenes de compra pendientes de migración
   */
  protected function processAllPendingPurchaseOrders(DatabaseSyncService $syncService): void
  {
    $pendingOrders = PurchaseOrder::whereIn('migration_status', [
      'pending',
      'in_progress',
//      'failed'
    ])
      ->whereNull('deleted_at')
      ->get();

    foreach ($pendingOrders as $order) {
      try {
        $this->processPurchaseOrder($order->id, $syncService);
      } catch (Exception $e) {
        Log::error("Error al procesar orden de compra ID {$order->id}: " . $e->getMessage());
        continue;
      }
    }
  }

  /**
   * Procesa una orden de compra específica
   */
  protected function processPurchaseOrder(int $purchaseOrderId, DatabaseSyncService $syncService): void
  {
    $purchaseOrder = PurchaseOrder::with(['supplier', 'vehicleMovement.vehicle.model', 'items'])->find($purchaseOrderId);

    if (!$purchaseOrder) {
      return;
    }

    // Actualizar estado general a 'in_progress'
    $purchaseOrder->update(['migration_status' => 'in_progress']);

    // Determinar si es OC de vehículos o genérica
    $isVehiclePO = !is_null($purchaseOrder->vehicle_movement_id);

    if ($isVehiclePO) {
      // Flujo para OC de vehículos (flujo original)
      $this->processVehiclePurchaseOrder($purchaseOrder, $syncService);
    } else {
      // Flujo para OC genéricas (productos, servicios, etc.)
      $this->processGenericPurchaseOrder($purchaseOrder, $syncService);
    }
  }

  /**
   * Procesa una orden de compra de vehículos
   */
  protected function processVehiclePurchaseOrder(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    // 1. Verificar y sincronizar proveedor
    $this->verifyAndSyncSupplier($purchaseOrder, $syncService);

    // 2. Verificar y sincronizar artículo
    $this->verifyAndSyncArticle($purchaseOrder, $syncService);

    // 3. Verificar y sincronizar orden de compra
    $this->verifyAndSyncPurchaseOrder($purchaseOrder, $syncService);

    // 4. Verificar y sincronizar recepción (solo si la OC está procesada)
    $this->verifyAndSyncReception($purchaseOrder, $syncService);

    // 5. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatus($purchaseOrder);
  }

  /**
   * Procesa una orden de compra genérica (productos, servicios, etc.)
   */
  protected function processGenericPurchaseOrder(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    // 1. Verificar y sincronizar proveedor
    $this->verifyAndSyncSupplier($purchaseOrder, $syncService);

    // 2. Verificar y sincronizar artículos (productos)
    $this->verifyAndSyncProducts($purchaseOrder, $syncService);

    // 3. Verificar y sincronizar orden de compra genérica
    $this->verifyAndSyncGenericPurchaseOrder($purchaseOrder, $syncService);

    // 4. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatusForGeneric($purchaseOrder);
  }

  /**
   * Verifica y sincroniza el proveedor
   */
  protected function verifyAndSyncSupplier(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $supplier = $purchaseOrder->supplier;

    if (!$supplier) {
      return;
    }

    // Obtener o crear log para el proveedor
    $supplierLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER],
      $supplier->num_doc
    );

    $supplierAddressLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER_ADDRESS,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER_ADDRESS],
      $supplier->num_doc
    );
    // Si ya está completado, no hacer nada
    if ($supplierLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
      $supplierAddressLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia
    $existingSupplier = DB::connection('dbtp')
      ->table('neInTbProveedor')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('NumeroDocumento', $supplier->num_doc)
      ->first();

    if (!$existingSupplier) {
      // No existe, intentar sincronizar
      try {
        $supplierLog->markAsInProgress();
        $syncService->sync('business_partners_ap_supplier', $supplier->toArray());
        $supplierLog->updateProcesoEstado(0);
        $supplierAddressLog->markAsInProgress();
        $syncService->sync('business_partners_directions_ap_supplier', $supplier->toArray());
        $supplierAddressLog->updateProcesoEstado(0);

      } catch (Exception $e) {
        Log::error('Error al sincronizar proveedor: ' . $e->getMessage());
        $supplierLog->markAsFailed("Error al sincronizar proveedor: {$e->getMessage()}");
      }
    } else {
      // Existe, actualizar el estado del log
      $supplierLog->updateProcesoEstado(
        $existingSupplier->ProcesoEstado ?? 0,
        $existingSupplier->ProcesoError ?? null
      );

      // Verificar dirección
      $existingAddress = DB::connection('dbtp')
        ->table('neInTbProveedorDireccion')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Proveedor', $supplier->num_doc)
        ->first();

      if ($existingAddress) {
        $supplierAddressLog->updateProcesoEstado(
          $existingAddress->ProcesoEstado ?? 0,
          $existingAddress->ProcesoError ?? null
        );
      }
    }
  }

  /**
   * Verifica y sincroniza el artículo
   */
  protected function verifyAndSyncArticle(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $model = $purchaseOrder->vehicleMovement?->vehicle?->model;

    if (!$model) {
      return;
    }

    $articleLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_ARTICLE,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ARTICLE],
      $model->code
    );

    // Si ya está completado, no hacer nada
    if ($articleLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia
    $existingArticle = DB::connection('dbtp')
      ->table('neInTbArticulo')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('Articulo', $model->code)
      ->first();

    if (!$existingArticle) {
      // No existe, despachar job para sincronizar
      try {
        $articleLog->markAsInProgress();
        SyncArticleJob::dispatch($model->id);
      } catch (Exception $e) {
        $articleLog->markAsFailed("Error al despachar job de artículo: {$e->getMessage()}");
      }
    } else {
      // Existe, actualizar el estado del log
      $articleLog->updateProcesoEstado(
        $existingArticle->ProcesoEstado ?? 0,
        $existingArticle->ProcesoError ?? null
      );
    }
  }

  /**
   * Verifica y sincroniza los artículos (productos) de una OC genérica
   */
  protected function verifyAndSyncProducts(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    // Obtener items con productos
    $items = $purchaseOrder->items()->with('product')->get();

    if ($items->isEmpty()) {
      return;
    }

    foreach ($items as $item) {
      if (!$item->product || !$item->product->dyn_code) {
        continue; // Saltar items sin producto o sin dyn_code
      }

      $product = $item->product;

      Log::info("OC {$purchaseOrder->number}: PROCESAMOS EL PRIMER PRODUCTO CON DYN_CODE {$product->dyn_code}");

      // Crear log para este producto (usar código único por producto)
      $articleLog = $this->getOrCreateLog(
        $purchaseOrder->id,
        VehiclePurchaseOrderMigrationLog::STEP_ARTICLE, // Step único por producto
        VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_ARTICLE], // Tabla dinámica por producto
        $product->dyn_code
      );

      // Si ya está completado, continuar con el siguiente
      if ($articleLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
        continue;
      }

      // Verificar en la BD intermedia
      $existingArticle = DB::connection('dbtp')
        ->table('neInTbArticulo')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('Articulo', $product->dyn_code)
        ->first();

      if (!$existingArticle) {
        // No existe, despachar job para sincronizar el producto
        try {
          $articleLog->markAsInProgress();
          SyncProductArticleJob::dispatch($product->id);
        } catch (Exception $e) {
          $articleLog->markAsFailed("Error al despachar job de artículo producto: {$e->getMessage()}");
        }
      } else {
        // Existe, actualizar el estado del log
        $articleLog->updateProcesoEstado(
          $existingArticle->ProcesoEstado ?? 0,
          $existingArticle->ProcesoError ?? null
        );
      }
    }
  }

  /**
   * Verifica y sincroniza la orden de compra
   */
  protected function verifyAndSyncPurchaseOrder(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $purchaseOrderLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER],
      $purchaseOrder->number
    );

    $purchaseOrderDetailLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL],
      $purchaseOrder->number
    );

    if ($purchaseOrderLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
      $purchaseOrderDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    $supplierLog = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER)
      ->first();

    $articleLog = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
      ->first();

    if (!$supplierLog || $supplierLog->proceso_estado !== 1) {
      return;
    }

    if (!$articleLog || $articleLog->proceso_estado !== 1) {
      return;
    }

    $existingPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $purchaseOrder->number)
      ->first();

    if (!$existingPO) {
      try {
        $resourcePurchaseOrder = new VehiclePurchaseOrderDynamicsResource($purchaseOrder);
        $resourceDataPurchaseOrder = $resourcePurchaseOrder->toArray(request());

        $resourcePurchaseOrderDetail = new VehiclePurchaseOrderDetailDynamicsResource($purchaseOrder);
        $resourceDataPurchaseOrderDetail = $resourcePurchaseOrderDetail->toArray(request());

        $purchaseOrderLog->markAsInProgress();
        $syncService->sync('ap_purchase_order', $resourceDataPurchaseOrder);
        $purchaseOrderLog->updateProcesoEstado(0);

        $purchaseOrderDetailLog->markAsInProgress();
        $syncService->sync('ap_purchase_order_item', $resourceDataPurchaseOrderDetail);
        $purchaseOrderDetailLog->updateProcesoEstado(0);
      } catch (Exception $e) {
        Log::error('Error al sincronizar orden de compra: ' . $e->getMessage());
        $purchaseOrderLog->markAsFailed("Error al sincronizar orden de compra: {$e->getMessage()}");
      }
    } else {
      $purchaseOrderLog->updateProcesoEstado(
        $existingPO->ProcesoEstado ?? 0,
        $existingPO->ProcesoError ?? null
      );
      $existingPODetail = DB::connection('dbtp')
        ->table('neInTbOrdenCompraDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('OrdenCompraId', $purchaseOrder->number)
        ->first();

      if ($existingPODetail) {
        $purchaseOrderDetailLog->updateProcesoEstado(1);
      }
    }
  }

  /**
   * Verifica y sincroniza la recepción
   */
  protected function verifyAndSyncReception(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    // Verificar que la OC esté procesada
    $purchaseOrderLog = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER)
      ->first();

    if (!$purchaseOrderLog) {
      return;
    }

    if ($purchaseOrderLog->proceso_estado !== 1) {
      return;
    }

    $receptionLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_RECEPTION,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION],
      $purchaseOrder->number_guide
    );

    $receptionDetailLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL],
      $purchaseOrder->number_guide
    );

    $receptionSerialLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_RECEPTION_DETAIL_SERIAL],
      $purchaseOrder->vehicleMovement?->vehicle?->vin
    );

    // Si ya está completado, no hacer nada
    if ($receptionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
      $receptionDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
      $receptionSerialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia
    $existingReception = DB::connection('dbtp')
      ->table('neInTbRecepcion')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('RecepcionId', $purchaseOrder->number_guide)
      ->first();

    if (!$existingReception) {
      // No existe, intentar sincronizar
      try {
        // Crear los 3 resources específicos para cada tabla de recepción
        $receptionResource = new PurchaseOrderVehicleReceptionResource($purchaseOrder);
        $receptionData = $receptionResource->toArray(request());

        $receptionDetailResource = new PurchaseOrderVehicleReceptionDetailResource($purchaseOrder);
        $receptionDetailData = $receptionDetailResource->toArray(request());

        $receptionSerialResource = new PurchaseOrderVehicleReceptionSerialResource($purchaseOrder);
        $receptionSerialData = $receptionSerialResource->toArray(request());

        // Sincronizar cabecera de recepción
        $receptionLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_reception', $receptionData, 'create');
        $receptionLog->updateProcesoEstado(0);

        // Sincronizar detalle de recepción
        $receptionDetailLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_reception_det', $receptionDetailData, 'create');
        $receptionDetailLog->updateProcesoEstado(0);

        // Sincronizar series/VIN de recepción
        $receptionSerialLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_reception_det_s', $receptionSerialData, 'create');
        $receptionSerialLog->updateProcesoEstado(0);

      } catch (Exception $e) {
        Log::error('Error al sincronizar recepción: ' . $e->getMessage());
        $receptionLog->markAsFailed("Error al sincronizar recepción: {$e->getMessage()}");
      }
    } else {
      // Existe, actualizar el estado del log
      $receptionLog->updateProcesoEstado(
        $existingReception->ProcesoEstado ?? 0,
        $existingReception->ProcesoError ?? null
      );

      // Verificar detalle
      $existingReceptionDetail = DB::connection('dbtp')
        ->table('neInTbRecepcionDt')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $purchaseOrder->number_guide)
        ->first();

      if ($existingReceptionDetail) {
        $receptionDetailLog->updateProcesoEstado(1);
      }

      // Verificar serial
      $existingReceptionSerial = DB::connection('dbtp')
        ->table('neInTbRecepcionDtS')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $purchaseOrder->number_guide)
        ->first();

      if ($existingReceptionSerial) {
        $receptionSerialLog->updateProcesoEstado(1);
      }
    }
  }

  /**
   * Verifica si todos los pasos están completos y actualiza el estado general
   */
  protected function checkAndUpdateCompletionStatus(PurchaseOrder $purchaseOrder): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)->get();

    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    if ($allCompleted && $logs->count() === 8) { // 8 pasos en total
      $purchaseOrder->update([
        'migration_status' => 'completed',
        'migrated_at' => now(),
      ]);
    } elseif ($hasFailed) {
      $purchaseOrder->update(['migration_status' => 'failed']);
    }
  }

  /**
   * Verifica y sincroniza una orden de compra genérica (sin artículos/vehículos)
   */
  protected function verifyAndSyncGenericPurchaseOrder(PurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $purchaseOrderLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER],
      $purchaseOrder->number
    );

    $purchaseOrderDetailLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL],
      $purchaseOrder->number
    );

    if ($purchaseOrderLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
      $purchaseOrderDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar que el proveedor esté procesado
    $supplierLog = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER)
      ->first();

    if (!$supplierLog || $supplierLog->proceso_estado !== 1) {
      return;
    }

    // Verificar que TODOS los productos (artículos) estén procesados
    $productLogs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_ARTICLE)
      ->get();

    // Obtener todos los productos únicos de los items de la OC
    $items = $purchaseOrder->items()->with('product')->get();
    $requiredProducts = $items->filter(function ($item) {
      return $item->product && $item->product->dyn_code;
    })->pluck('product.dyn_code')->unique();

    // Verificar que existan logs para todos los productos requeridos
    if ($requiredProducts->isNotEmpty()) {
      $processedProductCodes = $productLogs->pluck('external_id');

      // Verificar que todos los productos requeridos tengan un log
      foreach ($requiredProducts as $productCode) {
        if (!$processedProductCodes->contains($productCode)) {
          Log::info("OC {$purchaseOrder->number}: Falta log para el producto {$productCode}");
          return;
        }
      }

      // Verificar que todos los logs de productos estén procesados (proceso_estado = 1)
      $allProductsProcessed = $productLogs->every(function ($log) {
        return $log->proceso_estado === 1;
      });

      if (!$allProductsProcessed) {
        Log::info("OC {$purchaseOrder->number}: Esperando que todos los productos sean procesados en Dynamics");
        return;
      }
    }

    // Verificar si ya existe en la BD intermedia
    $existingPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $purchaseOrder->number)
      ->first();

    if (!$existingPO) {
      try {
        // Usar los resources genéricos para OC sin vehículos
        $resourcePurchaseOrder = new PurchaseOrderDynamicsResource($purchaseOrder);
        $resourceDataPurchaseOrder = $resourcePurchaseOrder->toArray(request());

        $resourcePurchaseOrderDetail = new PurchaseOrderItemDynamicsResource($purchaseOrder->items);
        $resourceDataPurchaseOrderDetail = $resourcePurchaseOrderDetail->toArray(request());

        // Sincronizar header
        $purchaseOrderLog->markAsInProgress();
        $syncService->sync('ap_purchase_order', $resourceDataPurchaseOrder, 'create');
        $purchaseOrderLog->updateProcesoEstado(0);

        // Sincronizar detalle (cada item)
        $purchaseOrderDetailLog->markAsInProgress();
        foreach ($resourceDataPurchaseOrderDetail as $detail) {
          $syncService->sync('ap_purchase_order_item', $detail, 'create');
        }
        $purchaseOrderDetailLog->updateProcesoEstado(0);
      } catch (Exception $e) {
        Log::error('Error al sincronizar orden de compra genérica: ' . $e->getMessage());
        $purchaseOrderLog->markAsFailed("Error al sincronizar orden de compra genérica: {$e->getMessage()}");
      }
    } else {
      $purchaseOrderLog->updateProcesoEstado(
        $existingPO->ProcesoEstado ?? 0,
        $existingPO->ProcesoError ?? null
      );

      $existingPODetail = DB::connection('dbtp')
        ->table('neInTbOrdenCompraDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('OrdenCompraId', $purchaseOrder->number)
        ->first();

      if ($existingPODetail) {
        $purchaseOrderDetailLog->updateProcesoEstado(1);
      }
    }
  }

  /**
   * Verifica si todos los pasos de una OC genérica están completos
   * OC genéricas tienen: supplier, supplier_address, article_* (dinámico), purchase_order, purchase_order_detail
   */
  protected function checkAndUpdateCompletionStatusForGeneric(PurchaseOrder $purchaseOrder): void
  {
    // Obtener todos los logs de esta OC
    $logs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)->get();

    // Pasos base requeridos
    $baseSteps = [
      VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER,
      VehiclePurchaseOrderMigrationLog::STEP_SUPPLIER_ADDRESS,
      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER,
      VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER_DETAIL,
    ];

    // Verificar que existan los pasos base
    foreach ($baseSteps as $step) {
      if (!$logs->where('step', $step)->first()) {
        return; // Falta algún paso base
      }
    }

    // Verificar que TODOS los logs estén completados con proceso_estado = 1
    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    if ($allCompleted) {
      $purchaseOrder->update([
        'migration_status' => 'completed',
        'migrated_at' => now(),
      ]);
    } elseif ($hasFailed) {
      $purchaseOrder->update(['migration_status' => 'failed']);
    }
  }

  /**
   * Obtiene o crea un registro de log
   */
  protected function getOrCreateLog(int $purchaseOrderId, string $step, string $tableName, ?string $externalId = null): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'vehicle_purchase_order_id' => $purchaseOrderId,
        'step' => $step,
      ],
      [
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        'table_name' => $tableName,
        'external_id' => $externalId,
      ]
    );
  }

  public function failed(\Throwable $exception): void
  {
  }
}
