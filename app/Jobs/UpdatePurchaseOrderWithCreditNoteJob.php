<?php

namespace App\Jobs;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePurchaseOrderWithCreditNoteJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $purchaseOrderId,
    public int $newPurchaseOrderId
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   * Actualiza los registros en la tabla intermedia apuntando a la nueva OC (con punto)
   * NO crea nuevos registros, ACTUALIZA los existentes
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      $originalPO = VehiclePurchaseOrder::find($this->purchaseOrderId);
      $newPO = VehiclePurchaseOrder::with(['supplier', 'model'])->find($this->newPurchaseOrderId);

      if (!$originalPO || !$newPO) {
        Log::error("Purchase orders not found: original={$this->purchaseOrderId}, new={$this->newPurchaseOrderId}");
        return;
      }

      // Verificar que la original tenga NC
      if (empty($originalPO->credit_note_dynamics)) {
        Log::warning("Original PO {$originalPO->id} does not have credit note, skipping");
        return;
      }

      Log::info("Processing intermediate DB update: {$originalPO->number} -> {$newPO->number}");

      // Paso 1: Verificar que la OC original esté migrada (ProcesoEstado = 1)
      if (!$this->verifyOriginalMigrationCompleted($originalPO)) {
        Log::info("Original migration not completed yet for PO: {$originalPO->number}");
        return;
      }

      // Paso 2: Sincronizar la nueva OC normalmente (sigue flujo estándar)
      $this->syncNewPurchaseOrder($newPO, $syncService);

      // Paso 3: Una vez sincronizada, actualizar los registros de la original en la intermedia
      if ($this->checkNewPurchaseOrderSynced($newPO)) {
        $this->updateIntermediateDBReferences($originalPO, $newPO);
      }

      // Paso 4: Verificar estado de actualización completa
      $this->checkUpdateCompletionStatus($newPO);

    } catch (\Exception $e) {
      Log::error("Error in UpdatePurchaseOrderWithCreditNoteJob: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Verifica que la migración original esté completada
   */
  protected function verifyOriginalMigrationCompleted(VehiclePurchaseOrder $purchaseOrder): bool
  {
    // Verificar en la BD intermedia que la OC original esté procesada
    $existingPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $purchaseOrder->number)
      ->first();

    if (!$existingPO) {
      Log::warning("Original PO not found in intermediate DB: {$purchaseOrder->number}");
      return false;
    }

    // Verificar que ProcesoEstado = 1 y ProcesoError esté vacío
    if ($existingPO->ProcesoEstado != 1 || !empty($existingPO->ProcesoError)) {
      Log::info("Original PO not fully processed yet: ProcesoEstado={$existingPO->ProcesoEstado}, ProcesoError={$existingPO->ProcesoError}");
      return false;
    }

    return true;
  }

  /**
   * Sincroniza la nueva OC siguiendo el flujo normal
   */
  protected function syncNewPurchaseOrder(VehiclePurchaseOrder $newPO, DatabaseSyncService $syncService): void
  {
    try {
      $resource = new VehiclePurchaseOrderResource($newPO);
      $resourceData = $resource->toArray(request());

      // Sincronizar OC y su detalle normalmente
      $syncService->sync('ap_vehicle_purchase_order', $resourceData, 'create');
      $syncService->sync('ap_vehicle_purchase_order_det', $resourceData, 'create');

      Log::info("New purchase order synced: {$newPO->number}");

      // Sincronizar recepción y detalles normalmente
      $syncService->sync('ap_vehicle_purchase_order_reception', $resourceData, 'create');
      $syncService->sync('ap_vehicle_purchase_order_reception_det', $resourceData, 'create');
      $syncService->sync('ap_vehicle_purchase_order_reception_det_s', $resourceData, 'create');

      Log::info("New reception synced: {$newPO->number_guide}");

    } catch (\Exception $e) {
      Log::error("Failed to sync new purchase order {$newPO->number}: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Verifica si la nueva OC ya fue sincronizada y procesada
   */
  protected function checkNewPurchaseOrderSynced(VehiclePurchaseOrder $newPO): bool
  {
    // Verificar OC
    $syncedPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $newPO->number)
      ->first();

    if (!$syncedPO || $syncedPO->ProcesoEstado != 1 || !empty($syncedPO->ProcesoError)) {
      Log::info("New PO not yet processed: {$newPO->number}");
      return false;
    }

    // Verificar Recepción
    $syncedReception = DB::connection('dbtp')
      ->table('neInTbRecepcion')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('RecepcionId', $newPO->number_guide)
      ->first();

    if (!$syncedReception || $syncedReception->ProcesoEstado != 1 || !empty($syncedReception->ProcesoError)) {
      Log::info("New reception not yet processed: {$newPO->number_guide}");
      return false;
    }

    return true;
  }

  /**
   * Actualiza los registros antiguos en la tabla intermedia apuntando a los nuevos números
   * Esto evita duplicados y mantiene la integridad referencial
   */
  protected function updateIntermediateDBReferences(VehiclePurchaseOrder $originalPO, VehiclePurchaseOrder $newPO): void
  {
    try {
      // Eliminar los registros de la OC nueva (se crearon temporalmente)
      DB::connection('dbtp')
        ->table('neInTbOrdenCompra')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('OrdenCompraId', $newPO->number)
        ->delete();

      DB::connection('dbtp')
        ->table('neInTbOrdenCompraDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('OrdenCompraId', $newPO->number)
        ->delete();

      // Actualizar la OC original con el nuevo número (con punto)
      DB::connection('dbtp')
        ->table('neInTbOrdenCompra')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('OrdenCompraId', $originalPO->number)
        ->update([
          'OrdenCompraId' => $newPO->number,
          'Procesar' => 1,
          'ProcesoEstado' => 0,
          'ProcesoError' => ''
        ]);

      DB::connection('dbtp')
        ->table('neInTbOrdenCompraDet')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('OrdenCompraId', $originalPO->number)
        ->update([
          'OrdenCompraId' => $newPO->number
        ]);

      Log::info("Updated PO references in intermediate DB: {$originalPO->number} -> {$newPO->number}");

      // Eliminar registros de la recepción nueva
      DB::connection('dbtp')
        ->table('neInTbRecepcion')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $newPO->number_guide)
        ->delete();

      DB::connection('dbtp')
        ->table('neInTbRecepcionDt')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $newPO->number_guide)
        ->delete();

      DB::connection('dbtp')
        ->table('neInTbRecepcionDtS')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $newPO->number_guide)
        ->delete();

      // Actualizar la recepción original con el nuevo número (con punto)
      DB::connection('dbtp')
        ->table('neInTbRecepcion')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $originalPO->number_guide)
        ->update([
          'RecepcionId' => $newPO->number_guide,
          'Procesar' => 1,
          'ProcesoEstado' => 0,
          'ProcesoError' => ''
        ]);

      DB::connection('dbtp')
        ->table('neInTbRecepcionDt')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $originalPO->number_guide)
        ->update([
          'RecepcionId' => $newPO->number_guide,
          'OrdenCompraId' => $newPO->number
        ]);

      DB::connection('dbtp')
        ->table('neInTbRecepcionDtS')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('RecepcionId', $originalPO->number_guide)
        ->update([
          'RecepcionId' => $newPO->number_guide
        ]);

      Log::info("Updated reception references in intermediate DB: {$originalPO->number_guide} -> {$newPO->number_guide}");

      $this->logAction($newPO->id, 'update_intermediate_refs', "Referencias actualizadas en tabla intermedia exitosamente");

    } catch (\Exception $e) {
      Log::error("Failed to update intermediate DB references: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Verifica y actualiza el estado de completitud de la actualización
   */
  protected function checkUpdateCompletionStatus(VehiclePurchaseOrder $newPO): void
  {
    // Verificar que los registros actualizados estén procesados
    $updatedPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $newPO->number)
      ->first();

    $updatedReception = DB::connection('dbtp')
      ->table('neInTbRecepcion')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('RecepcionId', $newPO->number_guide)
      ->first();

    if ($updatedPO && $updatedPO->ProcesoEstado == 1 &&
      $updatedReception && $updatedReception->ProcesoEstado == 1) {

      // Marcar como completado
      $newPO->update(['migration_status' => 'updated_with_nc']);
      Log::info("Purchase order update with NC completed: {$newPO->number}");
      $this->logAction($newPO->id, 'update_completed', "Actualización por NC completada exitosamente");
    }
  }

  /**
   * Obtiene o crea un log de migración
   */
  protected function getOrCreateLog(int $purchaseOrderId, string $step, string $table, ?string $reference): VehiclePurchaseOrderMigrationLog
  {
    return VehiclePurchaseOrderMigrationLog::firstOrCreate(
      [
        'vehicle_purchase_order_id' => $purchaseOrderId,
        'step' => $step,
      ],
      [
        'table_name' => $table,
        'reference' => $reference,
        'status' => VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
      ]
    );
  }

  /**
   * Registra una acción en el historial
   */
  protected function logAction(int $purchaseOrderId, string $action, string $message): void
  {
    VehiclePurchaseOrderMigrationLog::create([
      'vehicle_purchase_order_id' => $purchaseOrderId,
      'step' => "history_{$action}",
      'table_name' => 'history',
      'reference' => $action,
      'status' => VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED,
      'proceso_estado' => 1,
      'proceso_error' => null,
      'observations' => $message,
    ]);
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("Failed UpdatePurchaseOrderWithCreditNoteJob for PO {$this->purchaseOrderId}: {$exception->getMessage()}");
  }
}

