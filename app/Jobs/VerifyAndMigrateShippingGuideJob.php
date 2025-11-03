<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyAndMigrateShippingGuideJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 5;
  public int $timeout = 300;
  public int $backoff = 60; // Esperar 60 segundos entre reintentos

  /**
   * Create a new job instance.
   */
  public function __construct(
    public ?int $shippingGuideId = null
  )
  {
    $this->onQueue('sync');
  }

  /**
   * Execute the job.
   * Si se proporciona un ID, procesa solo esa guía
   * Si no, procesa todas las guías no migradas
   */
  public function handle(DatabaseSyncService $syncService): void
  {
    try {
      if ($this->shippingGuideId) {
        $this->processShippingGuide($this->shippingGuideId, $syncService);
      } else {
        $this->processAllPendingShippingGuides($syncService);
      }
    } catch (\Exception $e) {
      Log::error('Error en VerifyAndMigrateShippingGuideJob', [
        'shipping_guide_id' => $this->shippingGuideId,
        'error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Procesa todas las guías de remisión pendientes de migración
   */
  protected function processAllPendingShippingGuides(DatabaseSyncService $syncService): void
  {
    $pendingGuides = ShippingGuides::whereIn('migration_status', [
      'pending',
      'in_progress',
      'failed'
    ])->get();

    foreach ($pendingGuides as $guide) {
      try {
        $this->processShippingGuide($guide->id, $syncService);
      } catch (\Exception $e) {
        Log::error('Error procesando guía de remisión', [
          'shipping_guide_id' => $guide->id,
          'error' => $e->getMessage(),
        ]);
        continue;
      }
    }
  }

  /**
   * Procesa una guía de remisión específica
   */
  protected function processShippingGuide(int $shippingGuideId, DatabaseSyncService $syncService): void
  {
    $shippingGuide = ShippingGuides::with([
      'vehicleMovement.vehicle.model',
      'sedeTransmitter',
      'sedeReceiver'
    ])->find($shippingGuideId);

    if (!$shippingGuide) {
      return;
    }

    // Actualizar estado general a 'in_progress' si está pending
    if ($shippingGuide->migration_status === 'pending') {
      $shippingGuide->update(['migration_status' => 'in_progress']);
    }

    // 1. Verificar y actualizar estado de transferencia de inventario
    $this->verifyInventoryTransfer($shippingGuide);

    // 2. Verificar y actualizar estado de detalle de transferencia
    $this->verifyInventoryTransferDetail($shippingGuide);

    // 3. Verificar y actualizar estado de serial de transferencia
    $this->verifyInventoryTransferSerial($shippingGuide);

    // 4. Verificar si todo está completo
    $this->checkAndUpdateCompletionStatus($shippingGuide);
  }

  /**
   * Verifica el estado de la transferencia de inventario en la BD intermedia
   */
  protected function verifyInventoryTransfer(ShippingGuides $shippingGuide): void
  {
    $transferLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER)
      ->first();

    if (!$transferLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($transferLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia
    $existingTransfer = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', 'VEH-' . $shippingGuide->correlative)
      ->first();

    if ($existingTransfer) {
      // Actualizar el log con el estado de la BD intermedia
      $transferLog->updateProcesoEstado(
        $existingTransfer->ProcesoEstado ?? 0,
        $existingTransfer->ProcesoError ?? null
      );
    }
  }

  /**
   * Verifica el estado del detalle de transferencia en la BD intermedia
   */
  protected function verifyInventoryTransferDetail(ShippingGuides $shippingGuide): void
  {
    $detailLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_DETAIL)
      ->first();

    if (!$detailLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia
    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', 'VEH-' . $shippingGuide->correlative)
      ->first();

    if ($existingDetail) {
      // Actualizar el log con el estado de la BD intermedia
      $detailLog->updateProcesoEstado(
        $existingDetail->ProcesoEstado ?? 0,
        $existingDetail->ProcesoError ?? null
      );
    }
  }

  /**
   * Verifica el estado del serial de transferencia en la BD intermedia
   */
  protected function verifyInventoryTransferSerial(ShippingGuides $shippingGuide): void
  {
    $serialLog = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)
      ->where('step', VehiclePurchaseOrderMigrationLog::STEP_INVENTORY_TRANSFER_SERIAL)
      ->first();

    if (!$serialLog) {
      return;
    }

    // Si ya está completado, no hacer nada
    if ($serialLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    // Verificar en la BD intermedia
    $existingSerial = DB::connection('dbtp')
      ->table('neInTbTransferenciaInventarioDtS')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransferenciaId', 'VEH-' . $shippingGuide->correlative)
      ->where('Serie', $shippingGuide->vehicleMovement?->vehicle?->vin)
      ->first();

    if ($existingSerial) {
      // Actualizar el log con el estado de la BD intermedia
      $serialLog->updateProcesoEstado(
        $existingSerial->ProcesoEstado ?? 0,
        $existingSerial->ProcesoError ?? null
      );
    }
  }

  /**
   * Verifica si todos los pasos están completos y actualiza el estado general
   */
  protected function checkAndUpdateCompletionStatus(ShippingGuides $shippingGuide): void
  {
    $logs = VehiclePurchaseOrderMigrationLog::where('shipping_guide_id', $shippingGuide->id)->get();

    $allCompleted = $logs->every(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED &&
        $log->proceso_estado === 1;
    });

    $hasFailed = $logs->contains(function ($log) {
      return $log->status === VehiclePurchaseOrderMigrationLog::STATUS_FAILED;
    });

    if ($allCompleted && $logs->count() === 3) { // 3 pasos en total para shipping guides
      // Marcar la guía como sincronizada
      $shippingGuide->update([
        'status_dynamic' => 1,
        'migration_status' => 'completed',
        'migrated_at' => now(),
      ]);
      Log::info('Guía de remisión sincronizada completamente', ['id' => $shippingGuide->id]);
    } elseif ($hasFailed) {
      $shippingGuide->update([
        'status_dynamic' => 0,
        'migration_status' => 'failed',
      ]);
      Log::warning('Falló la sincronización de guía de remisión', ['id' => $shippingGuide->id]);
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('VerifyAndMigrateShippingGuideJob falló completamente', [
      'shipping_guide_id' => $this->shippingGuideId,
      'error' => $exception->getMessage(),
    ]);
  }
}
