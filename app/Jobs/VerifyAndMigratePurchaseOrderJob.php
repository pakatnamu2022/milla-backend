<?php

namespace App\Jobs;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderDetailDynamicsResource;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderDynamicsResource;
use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyAndMigratePurchaseOrderJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 5; // Aumentar intentos porque este job coordina todo el flujo
  public int $timeout = 300;
  public int $backoff = 60; // Esperar 60 segundos entre reintentos para dar tiempo al sistema intermedio

  /**
   * Create a new job instance.
   */
  public function __construct(
    public ?int $purchaseOrderId = null
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   * Si se proporciona un ID, procesa solo esa OC
   * Si no, procesa todas las OCs no migradas
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      if ($this->purchaseOrderId) {
        $this->processPurchaseOrder($this->purchaseOrderId, $syncService);
      } else {
        $this->processAllPendingPurchaseOrders($syncService);
      }
    } catch (\Exception $e) {
      // Log::error("Error in VerifyAndMigratePurchaseOrderJob: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Procesa todas las órdenes de compra pendientes de migración
   */
  protected function processAllPendingPurchaseOrders(DatabaseSyncService $syncService): void
  {
    $pendingOrders = VehiclePurchaseOrder::whereIn('migration_status', [
      'pending',
      'in_progress',
      'failed'
    ])->get();

    // Log::info("Processing {$pendingOrders->count()} pending purchase orders for migration");

    foreach ($pendingOrders as $order) {
      try {
        $this->processPurchaseOrder($order->id, $syncService);
      } catch (\Exception $e) {
        // Log::error("Failed to process purchase order {$order->id}: {$e->getMessage()}");
        // Continuar con la siguiente orden
        continue;
      }
    }
  }

  /**
   * Procesa una orden de compra específica
   */
  protected function processPurchaseOrder(int $purchaseOrderId, DatabaseSyncService $syncService): void
  {
    $purchaseOrder = VehiclePurchaseOrder::with(['supplier', 'model'])->find($purchaseOrderId);

    if (!$purchaseOrder) {
      // Log::error("Purchase order not found: {$purchaseOrderId}");
      return;
    }

    // Log::info("Processing purchase order: {$purchaseOrder->number}");

    // Actualizar estado general a 'in_progress'
    $purchaseOrder->update(['migration_status' => 'in_progress']);

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
   * Verifica y sincroniza el proveedor
   */
  protected function verifyAndSyncSupplier(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
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
        $syncService->sync('business_partners_ap_supplier', $supplier->toArray(), 'create');
        $supplierLog->updateProcesoEstado(0);

        $supplierAddressLog->markAsInProgress();
        $syncService->sync('business_partners_directions_ap_supplier', $supplier->toArray(), 'create');
        $supplierAddressLog->updateProcesoEstado(0);

        // Log::info("Supplier synced: {$supplier->num_doc}");
      } catch (\Exception $e) {
        $supplierLog->markAsFailed("Error al sincronizar proveedor: {$e->getMessage()}");
        // Log::error("Failed to sync supplier {$supplier->num_doc}: {$e->getMessage()}");
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
  protected function verifyAndSyncArticle(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $model = $purchaseOrder->model;

    if (!$model) {
      // Log::error("Model not found for purchase order: {$purchaseOrder->id}");
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
        // Log::info("Article sync job dispatched for model: {$model->code}");
      } catch (\Exception $e) {
        $articleLog->markAsFailed("Error al despachar job de artículo: {$e->getMessage()}");
        // Log::error("Failed to dispatch article sync job for model {$model->code}: {$e->getMessage()}");
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
   * Verifica y sincroniza la orden de compra
   */
  protected function verifyAndSyncPurchaseOrder(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
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
        $syncService->sync('ap_vehicle_purchase_order', $resourceDataPurchaseOrder);
        $purchaseOrderLog->updateProcesoEstado(0);

        $purchaseOrderDetailLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_det', $resourceDataPurchaseOrderDetail);
        $purchaseOrderDetailLog->updateProcesoEstado(0);
      } catch (\Exception $e) {
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
  protected function verifyAndSyncReception(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    // Verificar que la OC esté procesada
    $purchaseOrderLog = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_PURCHASE_ORDER)
      ->first();

    if (!$purchaseOrderLog) {
      // Log::warning("No se encontró log de OC para PO ID {$purchaseOrder->id}. No se puede sincronizar recepción.");
      return;
    }

    if ($purchaseOrderLog->proceso_estado !== 1) {
      // Log::info("Esperando a que la OC {$purchaseOrder->number} sea procesada (ProcesoEstado actual: {$purchaseOrderLog->proceso_estado}). Recepción se sincronizará en el próximo intento.");
      return;
    }

    // Log::info("OC {$purchaseOrder->number} está procesada (ProcesoEstado = 1). Procediendo con la sincronización de la recepción.");

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
      $purchaseOrder->vin
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
        $resource = new VehiclePurchaseOrderResource($purchaseOrder);
        $resourceData = $resource->toArray(request());

        $receptionLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_reception', $resourceData, 'create');
        $receptionLog->updateProcesoEstado(0);

        $receptionDetailLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_reception_det', $resourceData, 'create');
        $receptionDetailLog->updateProcesoEstado(0);

        $receptionSerialLog->markAsInProgress();
        $syncService->sync('ap_vehicle_purchase_order_reception_det_s', $resourceData, 'create');
        $receptionSerialLog->updateProcesoEstado(0);

        // Log::info("Reception synced: {$purchaseOrder->number_guide}");
      } catch (\Exception $e) {
        $receptionLog->markAsFailed("Error al sincronizar recepción: {$e->getMessage()}");
        // Log::error("Failed to sync reception {$purchaseOrder->number_guide}: {$e->getMessage()}");
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
  protected function checkAndUpdateCompletionStatus(VehiclePurchaseOrder $purchaseOrder): void
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
      // Log::info("Purchase order migration completed: {$purchaseOrder->number}");
    } elseif ($hasFailed) {
      $purchaseOrder->update(['migration_status' => 'failed']);
      // Log::warning("Purchase order migration has failed steps: {$purchaseOrder->number}");
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
    // Log::error("Failed VerifyAndMigratePurchaseOrderJob: {$exception->getMessage()}");
  }
}
