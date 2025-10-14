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
    public int $purchaseOrderId
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   * Actualiza una OC que tiene NC, agregando punto al final de number y number_guide
   * y actualizando los registros en Dynamics (no creando nuevos)
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      $purchaseOrder = VehiclePurchaseOrder::with(['supplier', 'model'])->find($this->purchaseOrderId);

      if (!$purchaseOrder) {
        Log::error("Purchase order not found: {$this->purchaseOrderId}");
        return;
      }

      // Verificar que tenga NC
      if (empty($purchaseOrder->credit_note_dynamics)) {
        Log::warning("Purchase order {$purchaseOrder->id} does not have credit note, skipping update");
        return;
      }

      Log::info("Processing purchase order update with credit note: {$purchaseOrder->number}");

      // Paso 1: Actualizar OC en Milla agregando punto al final
      $this->updatePurchaseOrderNumbers($purchaseOrder);

      // Paso 2: Verificar que la OC original esté migrada (ProcesoEstado = 1)
      if (!$this->verifyOriginalMigrationCompleted($purchaseOrder)) {
        Log::info("Original migration not completed yet for PO: {$purchaseOrder->number}");
        return;
      }

      // Paso 3: Actualizar tablas en Dynamics (OC y Detalle)
      $this->updatePurchaseOrderInDynamics($purchaseOrder, $syncService);

      // Paso 4: Actualizar tablas de Recepción en Dynamics
      $this->updateReceptionInDynamics($purchaseOrder, $syncService);

      // Paso 5: Verificar estado de actualización
      $this->checkUpdateCompletionStatus($purchaseOrder);

    } catch (\Exception $e) {
      Log::error("Error in UpdatePurchaseOrderWithCreditNoteJob: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Actualiza los números de OC y NI agregando un punto al final
   */
  protected function updatePurchaseOrderNumbers(VehiclePurchaseOrder $purchaseOrder): void
  {
    $originalNumber = $purchaseOrder->number;
    $originalGuide = $purchaseOrder->number_guide;

    // Solo agregar punto si no lo tiene ya
    $newNumber = !str_ends_with($originalNumber, '.') ? $originalNumber . '.' : $originalNumber;
    $newGuide = !str_ends_with($originalGuide, '.') ? $originalGuide . '.' : $originalGuide;

    if ($newNumber !== $originalNumber || $newGuide !== $originalGuide) {
      $purchaseOrder->update([
        'number' => $newNumber,
        'number_guide' => $newGuide,
      ]);

      Log::info("Updated PO numbers: {$originalNumber} -> {$newNumber}, {$originalGuide} -> {$newGuide}");

      // Registrar en historial
      $this->logAction($purchaseOrder->id, 'update_numbers',
        "Números actualizados por NC: OC {$originalNumber} -> {$newNumber}, NI {$originalGuide} -> {$newGuide}");
    }
  }

  /**
   * Verifica que la migración original esté completada
   */
  protected function verifyOriginalMigrationCompleted(VehiclePurchaseOrder $purchaseOrder): bool
  {
    // Obtener el número original (sin el punto)
    $originalNumber = rtrim($purchaseOrder->number, '.');

    // Verificar en la BD intermedia que la OC original esté procesada
    $existingPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('OrdenCompraId', $originalNumber)
      ->first();

    if (!$existingPO) {
      Log::warning("Original PO not found in intermediate DB: {$originalNumber}");
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
   * Actualiza la OC y su detalle en Dynamics
   */
  protected function updatePurchaseOrderInDynamics(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $purchaseOrderLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      'update_purchase_order_nc',
      'neInTbOrdenCompra',
      $purchaseOrder->number
    );

    $purchaseOrderDetailLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      'update_purchase_order_detail_nc',
      'neInTbOrdenCompraDet',
      $purchaseOrder->number
    );

    try {
      $resource = new VehiclePurchaseOrderResource($purchaseOrder);
      $resourceData = $resource->toArray(request());

      // Actualizar OC principal
      $purchaseOrderLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order', $resourceData, 'update');
      $purchaseOrderLog->updateProcesoEstado(0);

      Log::info("Purchase order update synced: {$purchaseOrder->number}");
      $this->logAction($purchaseOrder->id, 'update_po', "Orden de compra actualizada en Dynamics: {$purchaseOrder->number}");

      // Actualizar detalle de OC
      $purchaseOrderDetailLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_det', $resourceData, 'update');
      $purchaseOrderDetailLog->updateProcesoEstado(0);

      Log::info("Purchase order detail update synced: {$purchaseOrder->number}");
      $this->logAction($purchaseOrder->id, 'update_po_detail', "Detalle de OC actualizado en Dynamics: {$purchaseOrder->number}");

    } catch (\Exception $e) {
      $purchaseOrderLog->markAsFailed("Error al actualizar orden de compra: {$e->getMessage()}");
      Log::error("Failed to update purchase order {$purchaseOrder->number}: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Actualiza la recepción y sus detalles en Dynamics
   */
  protected function updateReceptionInDynamics(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    // Verificar que la OC actualizada esté procesada
    $purchaseOrderLog = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->where('step', 'update_purchase_order_nc')
      ->first();

    if (!$purchaseOrderLog || $purchaseOrderLog->proceso_estado !== 1) {
      Log::info("Waiting for updated purchase order to be processed before updating reception: {$purchaseOrder->number}");
      return;
    }

    $receptionLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      'update_reception_nc',
      'neInTbRecepcion',
      $purchaseOrder->number_guide
    );

    $receptionDetailLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      'update_reception_detail_nc',
      'neInTbRecepcionDet',
      $purchaseOrder->number_guide
    );

    $receptionSerialLog = $this->getOrCreateLog(
      $purchaseOrder->id,
      'update_reception_serial_nc',
      'neInTbRecepcionDetSerie',
      $purchaseOrder->vin
    );

    try {
      $resource = new VehiclePurchaseOrderResource($purchaseOrder);
      $resourceData = $resource->toArray(request());

      // Actualizar recepción
      $receptionLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_reception', $resourceData, 'update');
      $receptionLog->updateProcesoEstado(0);

      Log::info("Reception update synced: {$purchaseOrder->number_guide}");
      $this->logAction($purchaseOrder->id, 'update_reception', "Recepción actualizada en Dynamics: {$purchaseOrder->number_guide}");

      // Actualizar detalle de recepción
      $receptionDetailLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_reception_det', $resourceData, 'update');
      $receptionDetailLog->updateProcesoEstado(0);

      Log::info("Reception detail update synced: {$purchaseOrder->number_guide}");
      $this->logAction($purchaseOrder->id, 'update_reception_detail', "Detalle de recepción actualizado en Dynamics: {$purchaseOrder->number_guide}");

      // Actualizar serial de recepción
      $receptionSerialLog->markAsInProgress();
      $syncService->sync('ap_vehicle_purchase_order_reception_det_s', $resourceData, 'update');
      $receptionSerialLog->updateProcesoEstado(0);

      Log::info("Reception serial update synced: {$purchaseOrder->vin}");
      $this->logAction($purchaseOrder->id, 'update_reception_serial', "Serial de recepción actualizado en Dynamics: {$purchaseOrder->vin}");

    } catch (\Exception $e) {
      $receptionLog->markAsFailed("Error al actualizar recepción: {$e->getMessage()}");
      Log::error("Failed to update reception {$purchaseOrder->number_guide}: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Verifica y actualiza el estado de completitud de la actualización
   */
  protected function checkUpdateCompletionStatus(VehiclePurchaseOrder $purchaseOrder): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('vehicle_purchase_order_id', $purchaseOrder->id)
      ->whereIn('step', [
        'update_purchase_order_nc',
        'update_purchase_order_detail_nc',
        'update_reception_nc',
        'update_reception_detail_nc',
        'update_reception_serial_nc'
      ])
      ->get();

    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED;
    });

    if ($allCompleted) {
      $purchaseOrder->update(['migration_status' => 'updated_with_nc']);
      Log::info("Purchase order update with NC completed: {$purchaseOrder->number}");
      $this->logAction($purchaseOrder->id, 'update_completed', "Actualización por NC completada exitosamente");
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

