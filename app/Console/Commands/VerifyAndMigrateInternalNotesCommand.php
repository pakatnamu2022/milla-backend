<?php

namespace App\Console\Commands;

use App\Jobs\VerifyAndMigrateInternalNoteJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyAndMigrateInternalNotesCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'internal-notes:verify-migration {--all : Procesar todas las notas pendientes}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y migra notas internas pendientes a Dynamics. Omite las que tienen 3+ intentos fallidos';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    // Consultar notas internas que requieren procesamiento
    // EXCLUYE migration_status = 'failed' para evitar reprocesar notas bloqueadas
    $query = ApInternalNote::withTrashed()
      ->where(function ($q) {
        // Caso 1: Notas activas (no eliminadas) pendientes de migración
        // Solo pending e in_progress; EXCLUYE completed, skipped y FAILED
        $q->whereNull('deleted_at')
          ->whereIn('migration_status', [
            ApInternalNote::MIGRATION_STATUS_PENDING,
            ApInternalNote::MIGRATION_STATUS_IN_PROGRESS,
          ]);
      })
      ->orWhere(function ($q) {
        // Caso 2: Notas eliminadas (reversiones) con logs de reversión pendientes
        // Solo pending e in_progress; EXCLUYE failed para evitar ciclos infinitos
        $q->whereNotNull('deleted_at')
          ->whereHas('migrationLogs', function ($logQuery) {
            $logQuery->where('step', 'LIKE', '%REVERSAL%')
              ->whereIn('status', [
                VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
                VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
              ]);
          });
      });

    // Obtener las notas que cumplen los criterios
    $internalNotes = $query->get();

    if ($internalNotes->isEmpty()) {
      $this->info('No hay notas internas pendientes de procesamiento');
      return self::SUCCESS;
    }

    $processed = 0;
    $skipped = 0;

    foreach ($internalNotes as $internalNote) {
      // Verificar si tiene más de 3 intentos fallidos
      $maxAttemptsReached = $this->hasMaxAttemptsReached($internalNote);

      if ($maxAttemptsReached) {
        $skipped++;
        $this->warn("Nota interna ID {$internalNote->id} omitida: Alcanzó el límite de 3 intentos fallidos");
        continue;
      }

      // Determinar si es reversión
      $isReversal = $internalNote->trashed() ||
        VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
          ->where('step', 'LIKE', '%REVERSAL%')
          ->exists();

      // Despachar job
      try {
        VerifyAndMigrateInternalNoteJob::dispatch($internalNote->id, $isReversal);
        $processed++;
        $this->info("Job despachado para nota interna ID {$internalNote->id} (reversión: " . ($isReversal ? 'sí' : 'no') . ")");
      } catch (\Exception $e) {
        $this->error("Error despachando job para nota interna ID {$internalNote->id}: {$e->getMessage()}");
        Log::error('Error despachando VerifyAndMigrateInternalNoteJob', [
          'internal_note_id' => $internalNote->id,
          'error' => $e->getMessage(),
        ]);
      }
    }

    $this->info("Procesamiento completado: {$processed} jobs despachados, {$skipped} omitidos por límite de intentos");

    return self::SUCCESS;
  }

  /**
   * Verifica si una nota interna ha alcanzado el máximo de intentos fallidos (3)
   *
   * @param ApInternalNote $internalNote
   * @return bool
   */
  protected function hasMaxAttemptsReached(ApInternalNote $internalNote): bool
  {
    // Obtener el máximo de attempts de los logs relacionados
    $maxAttempts = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
      ->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL,
      ])
      ->max('attempts');

    // Si algún log alcanzó 6 attempts (equivalente a 3 intentos fallidos), omitir
    // Según MAX_PENDING_ATTEMPTS = 6, se bloquea antes del 4to intento
    return $maxAttempts !== null && $maxAttempts >= VehiclePurchaseOrderMigrationLog::MAX_PENDING_ATTEMPTS;
  }
}