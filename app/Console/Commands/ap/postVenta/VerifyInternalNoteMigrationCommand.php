<?php

namespace App\Console\Commands\ap\postVenta;

use App\Http\Services\DatabaseSyncService;
use App\Http\Services\ap\postventa\taller\dynamics\InternalNoteMigrationLogService;
use App\Jobs\VerifyAndMigrateInternalNoteJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use Illuminate\Console\Command;

class VerifyInternalNoteMigrationCommand extends Command
{
  /**
   * The name and signature of the console command.
   * @var string
   */
  protected $signature = 'internal-note:verify-migration
                          {--id= : ID de la nota interna específica}
                          {--all : Verificar todas las notas pendientes}
                          {--sync : Ejecutar inmediatamente sin usar cola}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica el estado de migración de notas internas en Dynamics y actualiza los registros';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $internalNoteId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    // Por defecto usa cola, solo sync si se especifica --sync
    $useSync = $sync;

    if ($internalNoteId) {
      // Verificar una nota específica
      $internalNote = ApInternalNote::find($internalNoteId);

      if (!$internalNote) {
        $this->error("✗ Nota interna no encontrada: {$internalNoteId}");
        return 1;
      }

      // Determinar si es reversión
      $isReversal = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNoteId)
        ->where('step', 'LIKE', '%REVERSAL%')
        ->exists();

      if ($useSync) {
        $this->info("🔍 Ejecutando verificación para la nota interna: {$internalNote->number}");
        $syncService = app(DatabaseSyncService::class);
        $logService = app(InternalNoteMigrationLogService::class);
        $job = new VerifyAndMigrateInternalNoteJob($internalNote->id, $isReversal);

        try {
          $job->handle($syncService, $logService);
          $this->newLine();
          $this->info("✓ Verificación completada.");
          $this->showStatus($internalNote);
        } catch (\Exception $e) {
          $this->error("✗ Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        $this->info("🚀 Despachando job de verificación para la nota interna: {$internalNote->number}");
        VerifyAndMigrateInternalNoteJob::dispatch($internalNote->id, $isReversal);
        $this->info("✓ Job despachado a la cola 'internal_notes'");
      }

      return 0;
    }

    if ($all) {
      $pendingNotes = ApInternalNote::whereIn('migration_status', [
        VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
        VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
      ])
        ->whereNull('deleted_at')
        ->orderBy('id', 'desc')
        ->get();

      if ($pendingNotes->isEmpty()) {
        $this->info("✓ No hay notas internas pendientes de migración.");
        return 0;
      }

      $this->info("📋 Encontradas {$pendingNotes->count()} notas internas pendientes de migración.");
      $this->newLine();

      $bar = $this->output->createProgressBar($pendingNotes->count());
      $bar->start();

      if ($useSync) {
        $syncService = app(DatabaseSyncService::class);
        $logService = app(InternalNoteMigrationLogService::class);

        foreach ($pendingNotes as $note) {
          try {
            $isReversal = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $note->id)
              ->where('step', 'LIKE', '%REVERSAL%')
              ->exists();

            $job = new VerifyAndMigrateInternalNoteJob($note->id, $isReversal);
            $job->handle($syncService, $logService);
          } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ Error en nota {$note->number}: {$e->getMessage()}");
          }
          $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Verificación completada.");
      } else {
        foreach ($pendingNotes as $note) {
          $isReversal = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $note->id)
            ->where('step', 'LIKE', '%REVERSAL%')
            ->exists();

          VerifyAndMigrateInternalNoteJob::dispatch($note->id, $isReversal);
          $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Jobs despachados a la cola 'internal_notes'");
        $this->line("  Ejecuta: php artisan queue:work --queue=internal_notes");
      }

      return 0;
    }

    // Si no se especifica ninguna opción, mostrar ayuda
    $this->error("✗ Debe especificar --id o --all para verificar notas internas.");
    $this->newLine();
    $this->line("Ejemplos:");
    $this->line("  php artisan internal-note:verify-migration --id=10");
    $this->line("  php artisan internal-note:verify-migration --all");
    $this->line("  php artisan internal-note:verify-migration --id=10 --sync");
    $this->line("  php artisan internal-note:verify-migration --all --sync");
    return 1;
  }

  /**
   * Muestra el estado actual de migración de una nota interna
   */
  protected function showStatus(ApInternalNote $internalNote): void
  {
    $this->newLine();
    $this->line("📊 ESTADO DE MIGRACIÓN:");
    $this->line("  Nota Interna: <fg=cyan>{$internalNote->number}</>");
    $this->line("  Estado: <fg=yellow>{$internalNote->migration_status}</>");
    $this->newLine();

    // Mostrar logs de migración
    $logs = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNote->id)
      ->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_REVERSAL,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL_REVERSAL,
      ])
      ->get();

    if ($logs->isNotEmpty()) {
      $headers = ['Step', 'Status', 'ProcesoEstado', 'Attempts', 'Error'];
      $rows = $logs->map(fn($log) => [
        $log->step,
        $log->status,
        $log->proceso_estado ?? 'N/A',
        $log->attempts,
        $log->error_message ? substr($log->error_message, 0, 50) . '...' : 'N/A',
      ])->toArray();

      $this->table($headers, $rows);
    }
  }
}