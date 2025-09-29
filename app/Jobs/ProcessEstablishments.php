<?php

namespace App\Jobs;

use App\Http\Services\DocumentValidation\DocumentValidationService;
use App\Models\ap\comercial\BusinessPartners;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessEstablishments implements ShouldQueue
{
  use Queueable;

  public int $tries = 3; // Intentos en caso de fallo
  public int $timeout = 60; // Timeout de 60 segundos

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int    $businessPartnerId,
    public string $numDoc
  )
  {
    $this->onQueue('establishments');
  }

  /**
   * Execute the job.
   */
  public function handle(DocumentValidationService $documentValidationService): void
  {
    $businessPartner = BusinessPartners::find($this->businessPartnerId);

    if (!$businessPartner) {
      Log::error("BusinessPartner not found: {$this->businessPartnerId}");
      return;
    }

    try {
      $establishments = $documentValidationService->validateDocument(
        'anexo',
        $this->numDoc
      );

      if ($establishments['success'] && !empty($establishments['data']['establishments'] ?? [])) {
        foreach ($establishments['data']['establishments'] as $establishment) {
          $businessPartner->establishments()->create([
            'code' => $establishment['code'] ?? null,
            'type' => $establishment['type'] ?? null,
            'activity_economic' => $establishment['activity_economic'] ?? null,
            'address' => $establishment['address'] ?? null,
            'full_address' => $establishment['full_address'] ?? null,
            'ubigeo' => $establishment['ubigeo_sunat'] ?? null,
            'business_partner_id' => $businessPartner->id,
          ]);
        }
      }

      $businessPartner->update(['establishments_status' => 'completed']);
    } catch (\Exception $e) {
      $businessPartner->update(['establishments_status' => 'failed']);
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    // Manejar el fallo del job
    Log::error("Failed to process establishments for BusinessPartner {$this->businessPartnerId}: {$exception->getMessage()}");
  }
}
