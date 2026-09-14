<?php

namespace App\Jobs;

use App\Http\Services\DocumentValidation\DocumentValidationService;
use App\Models\ap\comercial\BusinessPartners;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateEstablishments implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct(
    public int     $businessPartnerId,
    public string  $numDoc,
    public ?string $previousNumDoc = null
  )
  {
    $this->onQueue('update-establishments');
  }

  /**
   * Execute the job.
   */
  public function handle(DocumentValidationService $documentValidationService): void
  {
    $businessPartner = BusinessPartners::find($this->businessPartnerId);

    if (!$businessPartner) {
      // Log::error("BusinessPartner not found: {$this->businessPartnerId}");
      return;
    }

    try {
      // Obtener establecimientos actuales de la API
      $establishments = $documentValidationService->validateDocument(
        'anexo',
        $this->numDoc
      );

      if ($establishments['success'] && !empty($establishments['data']['establishments'] ?? [])) {
        $apiEstablishments = $establishments['data']['establishments'];

        // Si cambió el RUC, eliminar los establecimientos del RUC anterior ahora que la API respondió bien
        if ($this->previousNumDoc && $this->previousNumDoc !== $this->numDoc) {
          $businessPartner->establishments()->delete();
        }

        // Obtener códigos de establecimientos actuales en la BD
        $existingCodes = $businessPartner->establishments()->pluck('code')->toArray();

        // Obtener códigos de establecimientos de la API
        $apiCodes = collect($apiEstablishments)->pluck('code')->toArray();

        // Eliminar solo los establecimientos secundarios que ya no existen en la API
        // El código '0000' es el establecimiento principal y no lo retorna el endpoint anexo de SUNAT
        $codesToDelete = array_filter(
          array_diff($existingCodes, $apiCodes),
          fn($code) => $code !== '0000'
        );
        if (!empty($codesToDelete)) {
          $businessPartner->establishments()->whereIn('code', $codesToDelete)->delete();
        }

        // Crear o actualizar cada establecimiento de la API
        foreach ($apiEstablishments as $establishment) {
          $establishmentData = [
            'code'                => $establishment['code'] ?? null,
            'type'                => $establishment['type'] ?? null,
            'activity_economic'   => $establishment['activity_economic'] ?? null,
            'address'             => $establishment['address'] ?? '-',
            'full_address'        => $establishment['full_address'] ?? null,
            'ubigeo'              => $establishment['ubigeo_sunat'] ?? null,
            'business_partner_id' => $businessPartner->id,
          ];

          $businessPartner->establishments()->updateOrCreate(
            ['code' => $establishment['code'], 'business_partner_id' => $businessPartner->id],
            $establishmentData
          );
        }
      }
      // Si la API falla o devuelve vacío, no se toca ningún establecimiento existente

      $businessPartner->update(['establishments_status' => 'completed']);
    } catch (\Exception $e) {
      $businessPartner->update(['establishments_status' => 'failed']);
      // Log::error("Failed to update establishments for BusinessPartner {$this->businessPartnerId}: {$e->getMessage()}");
      throw $e;
    }
  }

  public function failed(\Throwable $exception): void
  {
    // Manejar el fallo del job
    // Log::error("Failed to update establishments for BusinessPartner {$this->businessPartnerId}: {$exception->getMessage()}");
  }
}
