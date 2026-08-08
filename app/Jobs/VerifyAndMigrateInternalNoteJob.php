<?php

namespace App\Jobs;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Http\Services\ap\postventa\taller\dynamics\InternalNoteDynamicsService;
use App\Http\Services\ap\postventa\taller\dynamics\InternalNoteMigrationLogService;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
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
    string      $queue = self::QUEUE_DEFAULT
  )
  {
    $this->onQueue($queue);
  }

  /**
   * @throws Exception
   */
  public function handle(
    DatabaseSyncService             $syncService,
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
        // Buscar con withTrashed() para manejar notas revertidas (soft-deleted)
        $internalNote = ApInternalNote::withTrashed()->find($this->internalNoteId);
        if ($internalNote) {
          $logService->checkAndUpdateCompletionStatus($internalNote, $this->isReversal);
        }
      }

      throw $e;
    }
  }

  protected function processAllPendingInternalNotes(
    InternalNoteMigrationLogService $logService,
    InternalNoteDynamicsService     $internalNoteService
  ): void
  {
    $pendingNotes = ApInternalNote::whereIn('migration_status', [
      ApInternalNote::MIGRATION_STATUS_PENDING,
      ApInternalNote::MIGRATION_STATUS_IN_PROGRESS,
      ApInternalNote::MIGRATION_STATUS_FAILED,
    ])
      ->whereNull('deleted_at')
      ->get();

    foreach ($pendingNotes as $note) {
      try {
        // Determinar si es reversión (nota soft-deleted o logs de reversión existentes)
        $isReversal = $note->trashed() ||
          VehiclePurchaseOrderMigrationLog::where('internal_note_id', $note->id)
            ->where('step', 'LIKE', '%REVERSAL%')
            ->exists();

        $this->processInternalNote($note->id, $isReversal, $logService, $internalNoteService);
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
    int                             $internalNoteId,
    bool                            $isReversal,
    InternalNoteMigrationLogService $logService,
    InternalNoteDynamicsService     $internalNoteService
  ): void
  {
    // Buscar con withTrashed() porque las reversiones trabajan con notas soft-deleted
    $internalNote = ApInternalNote::withTrashed()
      ->with(['workOrder.parts.product', 'workOrder.sede', 'workOrder.items.typePlanning'])
      ->find($internalNoteId);

    if (!$internalNote) {
      return;
    }

    if (!$internalNote->workOrder) {
      return;
    }

    // Verificar que la orden de trabajo tenga repuestos cargados
    if (!$internalNote->workOrder->parts || $internalNote->workOrder->parts->isEmpty()) {
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_SKIPPED]);
      return;
    }

    // Validar que sea tipo INTERNA_SC (las INTERNA_CC no generan movimientos de inventario)
    $typeDocument = $internalNote->workOrder->items->first()?->typePlanning->type_document;
    if ($typeDocument !== TypePlanningWorkOrder::INTERNA_SC) {
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_SKIPPED]);
      Log::info('Nota interna omitida: No es tipo INTERNA_SC', [
        'internal_note_id' => $internalNote->id,
        'type_document' => $typeDocument,
      ]);
      return;
    }

    // Actualizar estado a in_progress si está pendiente
    if ($internalNote->migration_status === ApInternalNote::MIGRATION_STATUS_PENDING) {
      $internalNote->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_IN_PROGRESS]);
    }

    // Reconstruir historial de costos para cada producto del movimiento de inventario
    $this->rebuildCostHistoryForInternalNote($internalNote, $isReversal);

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

  /**
   * Reconstruir historial de costos promedio ponderado para los productos de la nota interna
   *
   * @param ApInternalNote $internalNote
   * @param bool $isReversal
   * @return void
   */
  protected function rebuildCostHistoryForInternalNote(ApInternalNote $internalNote, bool $isReversal): void
  {
    try {
      // Obtener el movimiento de inventario asociado a esta nota interna
      $inventoryMovement = $internalNote->inventoryMovements()
        ->with(['details.product', 'warehouse'])
        ->where('movement_type', $isReversal
          ? InventoryMovement::TYPE_ADJUSTMENT_IN
          : InventoryMovement::TYPE_ADJUSTMENT_OUT)
        ->first();

      if (!$inventoryMovement || $inventoryMovement->details->isEmpty()) {
        Log::warning('No se encontró movimiento de inventario para reconstruir historial de costos', [
          'internal_note_id' => $internalNote->id,
          'is_reversal' => $isReversal,
        ]);
        return;
      }

      $stockService = app(ProductWarehouseStockService::class);

      // Reconstruir historial para cada producto del movimiento
      foreach ($inventoryMovement->details as $detail) {
        if (!$detail->product_id || !$inventoryMovement->warehouse_id) {
          continue;
        }

        try {
          $stockService->rebuildWeightedAverageCostHistory(
            $detail->product_id,
            $inventoryMovement->warehouse_id,
            null // Desde el inicio si es necesario
          );
        } catch (Exception $e) {
          Log::error('Error reconstruyendo historial de costos', [
            'product_id' => $detail->product_id,
            'warehouse_id' => $inventoryMovement->warehouse_id,
            'internal_note_id' => $internalNote->id,
            'error' => $e->getMessage(),
          ]);
          // Continuar con el siguiente producto
        }
      }
    } catch (Exception $e) {
      Log::error('Error general en rebuildCostHistoryForInternalNote', [
        'internal_note_id' => $internalNote->id,
        'error' => $e->getMessage(),
      ]);
      // No lanzar excepción para no detener el proceso de migración
    }
  }

  public function failed(\Throwable $exception): void
  {
    Log::error('VerifyAndMigrateInternalNoteJob falló completamente', [
      'internal_note_id' => $this->internalNoteId,
      'is_reversal' => $this->isReversal,
      'error' => $exception->getMessage(),
    ]);

    if ($this->internalNoteId) {
      // Incluir soft-deleted para poder actualizar notas revertidas
      ApInternalNote::withTrashed()
        ->where('id', $this->internalNoteId)
        ->update(['migration_status' => ApInternalNote::MIGRATION_STATUS_FAILED]);
    }
  }
}
