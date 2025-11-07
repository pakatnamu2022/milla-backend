<?php

namespace App\Jobs;

use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use App\Models\ap\facturacion\ElectronicDocument;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job para consultar el estado de un documento electrónico en Nubefact
 * Se ejecuta automáticamente para documentos en estado 'sent'
 *
 * php artisan queue:work --tries=2
 */
class CheckElectronicDocumentStatusJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 2;
  public int $timeout = 60;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int $documentId
  )
  {
    $this->onQueue('electronic-documents');
  }

  /**
   * Execute the job.
   */
  public function handle(ElectronicDocumentService $service): void
  {
    try {
      $document = ElectronicDocument::find($this->documentId);

      if (!$document) {
        Log::warning("CheckElectronicDocumentStatusJob: Document not found", [
          'document_id' => $this->documentId
        ]);
        return;
      }

      // Solo procesar documentos en estado 'sent' que no hayan sido aceptados y anulados
      if (
        !(
          ($document->status == ElectronicDocument::STATUS_SENT && !$document->aceptada_por_sunat)
          ||
          ($document->status == ElectronicDocument::STATUS_CANCELLED && $document->aceptada_por_sunat && !$document->anulado)
        )
      ) {
        Log::info("CheckElectronicDocumentStatusJob: Document not in valid state for checking", [
          'document_id' => $this->documentId,
          'status' => $document->status,
          'aceptada_por_sunat' => $document->aceptada_por_sunat,
          'anulado' => $document->anulado
        ]);
        return;
      }
      
      // Consultar estado en Nubefact (esto ya actualiza el documento automáticamente)
      $service->queryFromNubefact($this->documentId);

      Log::info("CheckElectronicDocumentStatusJob: Document status checked successfully", [
        'document_id' => $this->documentId,
        'serie' => $document->serie,
        'numero' => $document->numero
      ]);

    } catch (Exception $e) {
      Log::error("CheckElectronicDocumentStatusJob: Error checking document status", [
        'document_id' => $this->documentId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  /**
   * Handle a job failure.
   */
  public function failed(Throwable $exception): void
  {
    Log::error("CheckElectronicDocumentStatusJob: Job failed after all retries", [
      'document_id' => $this->documentId,
      'error' => $exception->getMessage()
    ]);
  }
}
