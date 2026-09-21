<?php

namespace App\Console\Commands;

use App\Jobs\VerifyAndMigrateTraverseJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyAndMigrateTraverseCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'traverse:verify-migration
                          {--document= : ID específico del documento a procesar}
                          {--all : Procesar todos los documentos pendientes}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Verifica y migra documentos con travesía pendientes a Dynamics. Omite los que tienen 3+ intentos fallidos';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    // Si se especifica un documento específico
    if ($documentId = $this->option('document')) {
      return $this->processDocument($documentId);
    }

    // Consultar documentos con travesía que requieren procesamiento
    $query = ElectronicDocument::where(function ($q) {
      // Documentos con travesía asociada y migración pendiente o en progreso
      $q->where('associate_purchase_traverse', true)
        ->whereIn('traverse_migration_status', ['pending', 'in_progress']);
    })
      ->orWhere(function ($q) {
        // Documentos con reversión pendiente (associate_purchase_traverse = false pero tiene logs REVERSAL)
        $q->where('associate_purchase_traverse', false)
          ->whereHas('migrationLogs', function ($logQuery) {
            $logQuery->where('step', 'LIKE', '%traverse%REVERSAL%')
              ->whereIn('status', [
                VehiclePurchaseOrderMigrationLog::STATUS_PENDING,
                VehiclePurchaseOrderMigrationLog::STATUS_IN_PROGRESS,
              ]);
          });
      });

    // Obtener los documentos que cumplen los criterios
    $documents = $query->get();

    if ($documents->isEmpty()) {
      $this->info('No hay documentos con travesía pendientes de procesamiento');
      return self::SUCCESS;
    }

    $this->info("Se encontraron {$documents->count()} documento(s) pendiente(s) de migración");

    $processed = 0;
    $skipped = 0;

    foreach ($documents as $document) {
      // Verificar si tiene más de 3 intentos fallidos
      $maxAttemptsReached = $this->hasMaxAttemptsReached($document);

      if ($maxAttemptsReached) {
        $skipped++;
        $this->warn("Documento {$document->full_number} (ID {$document->id}) omitido: Alcanzó el límite de 3 intentos fallidos");
        continue;
      }

      // Determinar si es reversión
      $isReversal = !$document->associate_purchase_traverse ||
        VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
          ->where('step', 'LIKE', '%traverse%REVERSAL%')
          ->exists();

      // Despachar job
      try {
        VerifyAndMigrateTraverseJob::dispatch($document->id, $isReversal);
        $processed++;
        $this->info("Job despachado para documento {$document->full_number} (ID {$document->id}) - reversión: " . ($isReversal ? 'sí' : 'no'));
      } catch (\Exception $e) {
        $this->error("Error despachando job para documento {$document->full_number} (ID {$document->id}): {$e->getMessage()}");
        Log::error('Error despachando VerifyAndMigrateTraverseJob', [
          'electronic_document_id' => $document->id,
          'error' => $e->getMessage(),
        ]);
      }
    }

    $this->info("Procesamiento completado: {$processed} jobs despachados, {$skipped} omitidos por límite de intentos");

    return self::SUCCESS;
  }

  /**
   * Procesa un documento específico
   */
  protected function processDocument(int $documentId): int
  {
    $document = ElectronicDocument::find($documentId);

    if (!$document) {
      $this->error("Documento ID {$documentId} no encontrado");
      return self::FAILURE;
    }

    // Verificar que tenga items en travesía
    $hasTraverseItems = $document->items()
      ->where('is_traverse', true)
      ->whereNotNull('product_id')
      ->exists();

    if (!$hasTraverseItems) {
      $this->error("El documento {$document->full_number} no tiene items en travesía");
      return self::FAILURE;
    }

    // Verificar intentos
    if ($this->hasMaxAttemptsReached($document)) {
      $this->warn("Documento {$document->full_number} alcanzó el límite de intentos fallidos");

      if (!$this->confirm('¿Desea forzar el procesamiento de todos modos?', false)) {
        return self::FAILURE;
      }
    }

    // Determinar si es reversión
    $isReversal = !$document->associate_purchase_traverse ||
      VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
        ->where('step', 'LIKE', '%traverse%REVERSAL%')
        ->exists();

    try {
      VerifyAndMigrateTraverseJob::dispatch($document->id, $isReversal);
      $this->info("Job despachado exitosamente para documento {$document->full_number} - reversión: " . ($isReversal ? 'sí' : 'no'));
      return self::SUCCESS;
    } catch (\Exception $e) {
      $this->error("Error despachando job: {$e->getMessage()}");
      return self::FAILURE;
    }
  }

  /**
   * Verifica si un documento ha alcanzado el máximo de intentos fallidos (3)
   */
  protected function hasMaxAttemptsReached(ElectronicDocument $document): bool
  {
    // Obtener el máximo de attempts de los logs relacionados con travesía
    $maxAttempts = VehiclePurchaseOrderMigrationLog::where('electronic_document_id', $document->id)
      ->where('step', 'LIKE', '%traverse%')
      ->max('attempts');

    // Si algún log alcanzó 6 attempts (equivalente a 3 intentos fallidos), omitir
    // Según MAX_PENDING_ATTEMPTS = 6, se bloquea antes del 4to intento
    return $maxAttempts !== null && $maxAttempts >= VehiclePurchaseOrderMigrationLog::MAX_PENDING_ATTEMPTS;
  }
}