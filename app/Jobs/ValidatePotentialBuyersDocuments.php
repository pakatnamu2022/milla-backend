<?php

namespace App\Jobs;

use App\Models\ap\comercial\PotentialBuyers;
use App\Http\Services\DocumentValidation\DocumentValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Illuminate\Support\Facades\Log;

class ValidatePotentialBuyersDocuments implements ShouldQueue
{
  use Queueable, InteractsWithQueue, SerializesModels;

  /**
   * Los IDs de los registros a validar
   *
   * @var array
   */
  protected $potentialBuyerIds;

  /**
   * Número de intentos antes de fallar
   *
   * @var int
   */
  public $tries = 3;

  /**
   * Timeout en segundos
   *
   * @var int
   */
  public $timeout = 600; // 10 minutos

  /**
   * Create a new job instance.
   *
   * @param array $potentialBuyerIds Array de IDs de PotentialBuyers a validar
   */
  public function __construct(array $potentialBuyerIds)
  {
    $this->onQueue('validate-potential-buyers-documents');
    $this->potentialBuyerIds = $potentialBuyerIds;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $documentValidationService = new DocumentValidationService();

    foreach ($this->potentialBuyerIds as $id) {
      try {
        // Obtener el registro
        $potentialBuyer = PotentialBuyers::find($id);

        if (!$potentialBuyer) {
          continue;
        }

        // Si ya tiene un estado validado, saltar
        if ($potentialBuyer->status_num_doc === 'VALIDADO') {
          continue;
        }

        $numDoc = $potentialBuyer->num_doc;

        // Validar el documento
        $validationResult = $this->validateDocument($documentValidationService, $numDoc);

        // Actualizar el estado
        $updateData = array_filter([
          'status_num_doc' => $validationResult['status'],
          'full_name' => $validationResult['full_name']
        ], fn($value) => !empty(trim($value)));

        $potentialBuyer->update($updateData);
      } catch (Exception $e) {
        // Continuar con el siguiente aunque falle uno
        continue;
      }
    }
  }

  /**
   * Valida un número de documento
   *
   * @param DocumentValidationService $service
   * @param string|null $numDoc
   * @return array ['status' => string, 'full_name' => string|null]
   */
  private function validateDocument(DocumentValidationService $service, $numDoc): array
  {
    // Si no hay número de documento
    if (empty($numDoc)) {
      return [
        'status' => 'ERRADO',
        'full_name' => null
      ];
    }

    // Limpiar el número de documento
    $numDoc = trim($numDoc);
    $length = strlen($numDoc);

    // Determinar el tipo de documento según la longitud
    $documentType = null;
    if ($length === 8) {
      $documentType = 'dni';
    } elseif ($length === 11) {
      $documentType = 'ruc';
    } else {
      return [
        'status' => 'ERRADO',
        'full_name' => null
      ];
    }

    try {
      // Validar el documento usando el servicio
      $result = $service->validateDocument(
        $documentType,
        $numDoc,
        [],
        true
      );

      // Extraer el nombre completo según el tipo
      $fullName = $result['data']['names'] ?? ($result['data']['business_name'] ?? null);

      // Verificar si la validación fue exitosa
      if (isset($result['success']) && $result['success'] === true) {
        return [
          'status' => 'VALIDADO',
          'full_name' => $fullName
        ];
      } else {
        return [
          'status' => 'NO_ENCONTRADO',
          'full_name' => null
        ];
      }
    } catch (Exception $e) {
      return [
        'status' => 'NO_ENCONTRADO',
        'full_name' => null
      ];
    }
  }

  /**
   * Handle a job failure.
   */
  public function failed(Exception $exception): void
  {
    Log::error('Job de validación de documentos falló: ' . $exception->getMessage(), [
      'total_ids' => count($this->potentialBuyerIds)
    ]);
  }
}
