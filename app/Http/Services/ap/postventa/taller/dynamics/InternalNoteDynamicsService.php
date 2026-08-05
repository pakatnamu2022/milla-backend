<?php

namespace App\Http\Services\ap\postventa\taller\dynamics;

use App\Http\Resources\Dynamics\InternalNoteTransactionDetailResource;
use App\Http\Resources\Dynamics\InternalNoteTransactionHeaderResource;
use App\Http\Services\DatabaseSyncService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InternalNoteDynamicsService
{
  public function __construct(
    protected DatabaseSyncService             $syncService,
    protected InternalNoteMigrationLogService $logService
  )
  {
  }

  /**
   * Verifica y sincroniza la cabecera de transacción de inventario (salida o ingreso)
   */
  public function verifyTransaction(ApInternalNote $internalNote, bool $isReversal = false): void
  {
    $step = $isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION;

    $transactionLog = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
      ->where('step', $step)
      ->first();

    if (!$transactionLog) {
      return;
    }

    if ($transactionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if ($this->logService->hasExceededAttemptLimit($transactionLog)) {
      Log::warning('[DynamicsService] verifyTransaction - Excedió límite de intentos', [
        'log_id' => $transactionLog->id,
        'attempts' => $transactionLog->attempts,
      ]);
      if ($transactionLog->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $transactionLog->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return;
    }

    $transactionId = $this->logService->buildInternalNoteTransactionId($internalNote, $isReversal);

    $existingTransaction = DB::connection('dbtp')
      ->table('neInTbTransaccionInventario')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->first();

    if (!$existingTransaction) {
      $this->syncTransaction($internalNote, $isReversal);
      return;
    }

    $transactionLog->updateProcesoEstado(
      $existingTransaction->ProcesoEstado ?? 0,
      $existingTransaction->ProcesoError ?? null,
      true
    );
  }

  /**
   * Verifica y sincroniza el detalle de transacción de inventario
   */
  public function verifyTransactionDetail(ApInternalNote $internalNote, bool $isReversal = false): void
  {
    $step = $isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL;

    $detailLog = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
      ->where('step', $step)
      ->first();

    if (!$detailLog) {
      return;
    }

    if ($detailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    if ($this->logService->hasExceededAttemptLimit($detailLog)) {
      if ($detailLog->status !== VehiclePurchaseOrderMigrationLog::STATUS_FAILED) {
        $detailLog->markAsFailed('Máximo de intentos alcanzado. Requiere intervención manual.');
      }
      return;
    }

    $transactionId = $this->logService->buildInternalNoteTransactionId($internalNote, $isReversal);

    $existingDetail = DB::connection('dbtp')
      ->table('neInTbTransaccionInventarioDet')
      ->where('EmpresaId', Company::AP_DYNAMICS)
      ->where('TransaccionId', $transactionId)
      ->first();

    if (!$existingDetail) {
      $this->syncTransactionDetail($internalNote, $isReversal);
      return;
    }

    // Si existe el detalle, marcarlo como completado
    $detailLog->updateProcesoEstado(1);
  }

  /**
   * Sincroniza la cabecera de transacción a Dynamics
   */
  protected function syncTransaction(ApInternalNote $internalNote, bool $isReversal): void
  {
    $step = $isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION;

    $transactionLog = $this->logService->getOrCreateLog(
      $internalNote->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION],
      $internalNote->number
    );

    if ($transactionLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      // Verificar si la cabecera ya existe antes de insertar
      $transactionId = $this->logService->buildInternalNoteTransactionId($internalNote, $isReversal);
      $existingTransaction = DB::connection('dbtp')
        ->table('neInTbTransaccionInventario')
        ->where('EmpresaId', Company::AP_DYNAMICS)
        ->where('TransaccionId', $transactionId)
        ->first();

      $transactionLog->markAsInProgress();

      // Solo insertar si no existe
      if (!$existingTransaction) {
        $resource = new InternalNoteTransactionHeaderResource($internalNote, $isReversal);
        $data = $resource->toArray(request());
        $this->syncService->sync('inventory_transaction', $data, 'create');
      } else {
        Log::info('[DynamicsService] syncTransaction - Cabecera ya existe, omitiendo inserción', [
          'TransaccionId' => $transactionId,
        ]);
      }

      $transactionLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('[DynamicsService] syncTransaction - ERROR', [
        'internal_note_id' => $internalNote->id,
        'is_reversal' => $isReversal,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transactionLog->markAsFailed("Error al sincronizar transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }

  /**
   * Sincroniza el detalle de transacción a Dynamics
   */
  protected function syncTransactionDetail(ApInternalNote $internalNote, bool $isReversal): void
  {
    $step = $isReversal
      ? VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL
      : VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL;

    $transactionDetailLog = $this->logService->getOrCreateLog(
      $internalNote->id,
      $step,
      VehiclePurchaseOrderMigrationLog::STEP_TABLE_MAPPING[VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL],
      $internalNote->number
    );

    if ($transactionDetailLog->status === VehiclePurchaseOrderMigrationLog::STATUS_COMPLETED) {
      return;
    }

    try {
      $resource = new InternalNoteTransactionDetailResource($internalNote, $isReversal);
      $details = $resource->toArray(request());
      $transactionDetailLog->markAsInProgress();

      // Sincronizar cada detalle por separado, verificando si ya existe
      foreach ($details as $index => $detail) {
        // Verificar si esta línea específica ya existe en Dynamics
        $existingLine = DB::connection('dbtp')
          ->table('neInTbTransaccionInventarioDet')
          ->where('EmpresaId', $detail['EmpresaId'])
          ->where('TransaccionId', $detail['TransaccionId'])
          ->where('Linea', $detail['Linea'])
          ->first();

        // Solo sincronizar si no existe
        if (!$existingLine) {
          $this->syncService->sync('inventory_transaction_dt', $detail, 'create');
        } else {
          Log::info('[DynamicsService] syncTransactionDetail - Línea ya existe, omitiendo', [
            'TransaccionId' => $detail['TransaccionId'],
            'Linea' => $detail['Linea'],
            'ArticuloId' => $detail['ArticuloId'],
          ]);
        }
      }

      $transactionDetailLog->updateProcesoEstado(0);
    } catch (Exception $e) {
      Log::error('Error al sincronizar detalle de transacción de inventario (InternalNote)', [
        'internal_note_id' => $internalNote->id,
        'is_reversal' => $isReversal,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      $transactionDetailLog->markAsFailed("Error al sincronizar detalle de transacción de inventario: {$e->getMessage()}");
      throw $e;
    }
  }
}
