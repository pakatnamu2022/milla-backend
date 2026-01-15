<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ValidatesPendingJobs;
use App\Http\Services\DatabaseSyncService;
use App\Jobs\SyncSalesDocumentJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyElectronicDocumentSyncCommand extends Command
{
  use ValidatesPendingJobs;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'electronic-document:verify-sync {--id= : ID del documento electrónico específico} {--all : Verificar todos los documentos pendientes} {--limit=200 : Número máximo de documentos a procesar (default: 200)} {--sync : Ejecutar inmediatamente sin usar cola}';

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
    $useSync = $this->option('sync');

    if ($documentId) {
      return $this->processSingleDocument($documentId, $useSync);
    }

    if ($all) {
      return $this->processAllPendingDocuments($useSync);
    }

    $this->showHelp();
    return 1;
  }

  /**
   * Procesa un documento electrónico específico
   */
  private function processSingleDocument(int $documentId, bool $useSync): int
  {
    $document = ElectronicDocument::whereIn('id', [$documentId])
      ->whereNull('deleted_at')
      ->where('status', ElectronicDocument::STATUS_ACCEPTED)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->first();

    if (!$document) {
      $this->error("Documento electrónico no encontrado: {$documentId}");
      return 1;
    }

    $this->info("Procesando documento: {$document->full_number}");

    return $this->executeSyncJob($document, $useSync) ? 0 : 1;
  }

  /**
   * Procesa todos los documentos electrónicos pendientes
   */
  private function processAllPendingDocuments(bool $useSync): int
  {
    // Validar límite de jobs pendientes antes de despachar (solo si usa cola)
    if (!$useSync && !$this->canDispatchMoreJobs(SyncSalesDocumentJob::class)) {
      return 0;
    }

    $pendingDocuments = $this->getPendingDocuments();

    if ($pendingDocuments->isEmpty()) {
      $this->info("No hay documentos electrónicos pendientes de sincronización.");
      return 0;
    }

    $this->info("Encontrados {$pendingDocuments->count()} documentos pendientes de sincronización.");

    $bar = $this->output->createProgressBar($pendingDocuments->count());
    $bar->start();

    $syncService = $useSync ? app(DatabaseSyncService::class) : null;

    foreach ($pendingDocuments as $document) {
      $this->executeSyncJob($document, $useSync, $syncService);
      $bar->advance();
    }

    $bar->finish();
    $this->newLine();

    $message = $useSync
      ? "✓ Sincronización completada."
      : "Jobs despachados a la cola.";

    $this->info($message);

    return 0;
  }

  /**
   * Obtiene los documentos electrónicos pendientes de sincronización
   */
  private function getPendingDocuments()
  {
    $limit = (int) $this->option('limit');

    $pendingDocumentIds = VehiclePurchaseOrderMigrationLog::whereNotNull('electronic_document_id')
      ->whereIn('status', [
        VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
        VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
        VehiclePurchaseOrderMigrationLog::STATUS_FAILED,
      ])
      ->distinct()
      ->pluck('electronic_document_id');

    return ElectronicDocument::whereIn('id', $pendingDocumentIds)
      ->where('status', ElectronicDocument::STATUS_ACCEPTED)
      ->where('anulado', false)
      ->where('aceptada_por_sunat', true)
      ->whereNull('deleted_at')
      ->orderBy('id')
      ->limit($limit)
      ->get();
  }

  /**
   * Ejecuta el job de sincronización de forma síncrona o asíncrona
   */
  private function executeSyncJob(ElectronicDocument $document, bool $useSync, ?DatabaseSyncService $syncService = null): bool
  {
    if ($useSync) {
      return $this->executeSyncJobSynchronously($document, $syncService);
    }

    return $this->dispatchSyncJobToQueue($document);
  }

  /**
   * Ejecuta el job de sincronización de forma síncrona
   */
  private function executeSyncJobSynchronously(ElectronicDocument $document, ?DatabaseSyncService $syncService = null): bool
  {
    try {
      $syncService = $syncService ?? app(DatabaseSyncService::class);
      $job = new SyncSalesDocumentJob($document->id);
      $job->handle($syncService);

      return true;
    } catch (\Exception $e) {
      $this->newLine();
      $this->error("Error en documento {$document->full_number}: {$e->getMessage()}");
      return false;
    }
  }

  /**
   * Despacha el job de sincronización a la cola
   */
  private function dispatchSyncJobToQueue(ElectronicDocument $document): bool
  {
    SyncSalesDocumentJob::dispatch($document->id);
    return true;
  }

  /**
   * Muestra la ayuda del comando
   */
  private function showHelp(): void
  {
    $this->warn("Debes especificar --id o --all para ejecutar este comando.");
    $this->info("Ejemplos:");
    $this->line("  php artisan electronic-document:verify-sync --id=123");
    $this->line("  php artisan electronic-document:verify-sync --all");
    $this->line("  php artisan electronic-document:verify-sync --all --sync");
  }
}
