<?php

namespace App\Console\Commands;

use App\Jobs\CheckElectronicDocumentStatusJob;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingElectronicDocuments extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:check-pending-electronic-documents';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Consulta el estado en SUNAT de documentos electrónicos enviados pero aún no aceptados';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    try {
      // Buscar documentos en estado 'sent' que aún no han sido aceptados
      // y que fueron enviados hace al menos 30 segundos pero no más de 5 minutos
      Log::info('CheckPendingElectronicDocuments: Starting to check pending electronic documents');
      $pendingDocuments = ElectronicDocument::where(function ($query) {
        $query->where('aceptada_por_sunat', false)
          ->orWhere(function ($q) {
            $q->where('aceptada_por_sunat', true)
              ->where('anulado', 0)
              ->where('status', ElectronicDocument::STATUS_CANCELLED);
          });
      })
        ->get();

      $count = $pendingDocuments->count();

      if ($count === 0) {
        Log::debug('CheckPendingElectronicDocuments: No pending documents found');
        return Command::SUCCESS;
      }

      Log::info("CheckPendingElectronicDocuments: Found {$count} pending documents to check", [
        'count' => $count
      ]);

      // Dispatch un job por cada documento pendiente
      foreach ($pendingDocuments as $document) {
        CheckElectronicDocumentStatusJob::dispatch($document->id);

        Log::debug("CheckPendingElectronicDocuments: Job dispatched for document", [
          'document_id' => $document->id,
          'serie' => $document->serie,
          'numero' => $document->numero
        ]);
      }

      Log::info("CheckPendingElectronicDocuments: Successfully dispatched {$count} jobs");

      return Command::SUCCESS;

    } catch (\Exception $e) {
      Log::error('CheckPendingElectronicDocuments: Error executing command', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

      return Command::FAILURE;
    }
  }
}
