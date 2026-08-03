<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\ap\postventa\taller\dynamics\InternalNoteDynamicsService;
use App\Http\Services\ap\postventa\taller\dynamics\InternalNoteMigrationLogService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Migra notas internas del área POSTVENTA/TALLER hacia Microsoft Dynamics 365
 * escribiendo en la base de datos intermedia (dbtp) y verificando su procesamiento.
 *
 * FLUJO GENERAL:
 *   1. Toma notas internas con migration_status = pending/in_progress/failed.
 *   2. Escribe en neInTbTransaccionInventario (cabecera y detalle):
 *      - Salida de inventario → TransaccionId = NIP-00001
 *      - Reversión (ingreso)  → TransaccionId = NIP-00001*
 *   3. Para cada paso verifica si el registro YA existe en la BD intermedia:
 *      - Si NO existe → lo sincroniza (inserta) y marca ProcesoEstado = 0 (en espera).
 *      - Si existe    → lee ProcesoEstado devuelto por Dynamics:
 *          * 0 = aún procesando → sin cambios.
 *          * 1 = aceptado       → marca el step como completado.
 *   4. Cuando ambos pasos están en ProcesoEstado = 1, marca la nota como
 *      migration_status = completed.
 *
 * PUEDE DESPACHARSE:
 *   - Con un $internalNoteId específico → procesa solo esa nota.
 *   - Sin ID                           → procesa todas las notas pendientes.
 *
 * COLA: internal_notes | tries: 2 | timeout: 300 s | backoff: 120 s
 */
class VerifyAndMigrateInternalNoteJob implements ShouldQueue
{
  use Queueable;

  const QUEUE_DEFAULT = 'internal_notes';

  public int $tries = 2;
  public int $timeout = 300;
  public int $backoff = 120;

  public function __construct(
    public ?int $internalNoteId = null,
    public bool $isReversal = false,
    string $queue = self::QUEUE_DEFAULT
  )
  {
    $this->onQueue($queue);
  }

  /**
   * @throws Exception
   */
  public function handle(
    DatabaseSyncService $syncService,
    InternalNoteMigrationLogService $logService
  ): void
  {
    $internalNoteService = new InternalNoteDynamicsService($syncService, $logService);

    try {
      if ($this->internalNoteId) {
        $this->processInternalNote($this->internalNoteId, $this->isReversal, $logService, $internalNoteService);
      } else {
        $this->processAllPendingInternalNotes($logService, $internalNoteService);
      }
    } catch (Exception $e) {
      Log::error('Error en VerifyAndMigrateInternalNoteJob', [
        'internal_note_id' => $this->internalNoteId,
        'is_reversal' => $this->isReversal,
        'error' => $e->getMessage(),
      ]);

      if ($this->internalNoteId) {
        $internalNote = ApInternalNote::find($this->internalNoteId);
        if ($internalNote) {
          $logService->checkAndUpdateCompletionStatus($internalNote, $this->isReversal);
        }
      }

      throw $e;
    }
  }

  protected function processAllPendingInternalNotes(
    InternalNoteMigrationLogService $logService,
    InternalNoteDynamicsService $internalNoteService
  ): void
  {
    $pendingNotes = ApInternalNote::whereIn('migration_status', [
      VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
      VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
      VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
    ])
      ->whereNull('deleted_at')
      ->get();

    foreach ($pendingNotes as $note) {
      try {
        // Determinar si es reversión basándose en la existencia de logs de reversión
        $hasReversalLogs = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $note->id)
          ->where('step', 'LIKE', '%REVERSAL%')
          ->exists();

        $this->processInternalNote($note->id, $hasReversalLogs, $logService, $internalNoteService);
      } catch (Exception $e) {
        Log::error('Error procesando nota interna', [
          'internal_note_id' => $note->id,
          'error' => $e->getMessage(),
        ]);
        continue;
      }
    }
  }

  protected function processInternalNote(
    int $internalNoteId,
    bool $isReversal,
    InternalNoteMigrationLogService $logService,
    InternalNoteDynamicsService $internalNoteService
  ): void
  {
    $internalNote = ApInternalNote::with(['workOrder.parts.product', 'workOrder.sede'])
      ->find($internalNoteId);

    if (!$internalNote) {
      return;
    }

    if (!$internalNote->workOrder) {
      Log::error('Nota interna sin orden de trabajo asociada', [
        'internal_note_id' => $internalNote->id,
      ]);
      return;
    }

    // Actualizar estado a in_progress si está pendiente
    if ($internalNote->migration_status === VehiclePurchaseOrderMigrationLog::STATUS_PENDING) {
      $internalNote->update(['migration_status' => VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS]);
    }

    // Asegurar que existan los logs
    if ($isReversal) {
      $logService->ensureInternalNoteReversalLogsExist($internalNote);
    } else {
      $logService->ensureInternalNoteLogsExist($internalNote);
    }

    // Primero sincronizar el detalle, luego la cabecera (orden inverso a la lectura)
    $internalNoteService->verifyTransactionDetail($internalNote, $isReversal);
    $internalNoteService->verifyTransaction($internalNote, $isReversal);

    // Verificar si todos los pasos están completados
    $logService->checkAndUpdateCompletionStatus($internalNote, $isReversal);
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('VerifyAndMigrateInternalNoteJob falló completamente', [
      'internal_note_id' => $this->internalNoteId,
      'is_reversal' => $this->isReversal,
      'error' => $exception->getMessage(),
    ]);

    if ($this->internalNoteId) {
      ApInternalNote::where('id', $this->internalNoteId)
        ->update(['migration_status' => 'failed']);
    }
  }
}