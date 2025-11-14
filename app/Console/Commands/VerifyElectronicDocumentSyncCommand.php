<?php

namespace App\Console\Commands;

use App\Http\Services\DatabaseSyncService;
use App\Jobs\SyncSalesDocumentJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyElectronicDocumentSyncCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'electronic-document:verify-sync {--id= : ID del documento electrónico específico} {--all : Verificar todos los documentos pendientes} {--sync : Ejecutar inmediatamente sin usar cola}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y sincroniza documentos electrónicos de venta pendientes a Dynamics';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $documentId = $this->option('id');
    $all = $this->option('all');
    $sync = $this->option('sync');

    // Por defecto usa cola, solo sync si se especifica --sync
    $useSync = $sync;

    if ($documentId) {
      // Verificar un documento específico
      $document = ElectronicDocument::find($documentId);

      if (!$document) {
        $this->error("Documento electrónico no encontrado: {$documentId}");
        return 1;
      }

      if ($useSync) {
        $this->info("Ejecutando sincronización para el documento: {$document->full_number}");
        $syncService = app(DatabaseSyncService::class);
        $job = new SyncSalesDocumentJob($document->id);

        try {
          $job->handle($syncService);
          $this->info("✓ Sincronización completada.");
        } catch (\Exception $e) {
          $this->error("Error: {$e->getMessage()}");
          return 1;
        }
      } else {
        $this->info("Despachando job de sincronización para el documento: {$document->full_number}");
        SyncSalesDocumentJob::dispatch($document->id);
        $this->info("Job despachado a la cola.");
      }

      return 0;
    }

    if ($all) {
      // Verificar todos los documentos con sincronización pendiente
      $pendingDocumentIds = VehiclePurchaseOrderMigrationLog::whereNotNull('electronic_document_id')
        ->whereIn('status', [
          VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
          VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
          VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
        ])
        ->distinct()
        ->pluck('electronic_document_id');

      if ($pendingDocumentIds->isEmpty()) {
        $this->info("No hay documentos electrónicos pendientes de sincronización.");
        return 0;
      }

      $pendingDocuments = ElectronicDocument::whereIn('id', $pendingDocumentIds)->get();

      $this->info("Encontrados {$pendingDocuments->count()} documentos pendientes de sincronización.");

      $bar = $this->output->createProgressBar($pendingDocuments->count());
      $bar->start();
      if ($useSync) {
        $syncService = app(DatabaseSyncService::class);
        foreach ($pendingDocuments as $document) {
          try {
            $job = new SyncSalesDocumentJob($document->id);
            $job->handle($syncService);
          } catch (\Exception $e) {
            $this->newLine();
            $this->error("Error en documento {$document->full_number}: {$e->getMessage()}");
          }
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Sincronización completada.");
      } else {
        foreach ($pendingDocuments as $document) {
          SyncSalesDocumentJob::dispatch($document->id);
          $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Jobs despachados a la cola.");
      }

      return 0;
    }

    // Si no se especifica ninguna opción, mostrar ayuda
    $this->warn("Debes especificar --id o --all para ejecutar este comando.");
    $this->info("Ejemplos:");
    $this->line("  php artisan electronic-document:verify-sync --id=123");
    $this->line("  php artisan electronic-document:verify-sync --all");
    $this->line("  php artisan electronic-document:verify-sync --all --sync");

    return 1;
  }
}
