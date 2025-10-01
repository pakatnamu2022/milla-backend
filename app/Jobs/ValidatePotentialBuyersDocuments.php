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
    $this->potentialBuyerIds = $potentialBuyerIds;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $documentValidationService = new DocumentValidationService();

    Log::info('Iniciando validación de documentos', [
      'total_records' => count($this->potentialBuyerIds)
    ]);

    $processed = 0;
    $validated = 0;
    $notFound = 0;
    $errors = 0;

    foreach ($this->potentialBuyerIds as $id) {
      try {
        // Obtener el registro
        $potentialBuyer = PotentialBuyers::find($id);

        if (!$potentialBuyer) {
          Log::warning("Registro no encontrado: {$id}");
          continue;
        }

        // Si ya tiene un estado validado, saltar
        if ($potentialBuyer->status_num_doc === 'VALIDADO') {
          continue;
        }

        $numDoc = $potentialBuyer->num_doc;

        // Validar el documento
        $statusNumDoc = $this->validateDocument($documentValidationService, $numDoc);

        // Actualizar el estado
        $potentialBuyer->update(['status_num_doc' => $statusNumDoc]);

        $processed++;

        // Contar estadísticas
        if ($statusNumDoc === 'VALIDADO') {
          $validated++;
        } elseif ($statusNumDoc === 'NO_ENCONTRADO') {
          $notFound++;
        } elseif ($statusNumDoc === 'ERRADO') {
          $errors++;
        }

        // Log cada 100 registros procesados
        if ($processed % 100 === 0) {
          Log::info("Progreso de validación: {$processed} de " . count($this->potentialBuyerIds));
        }

      } catch (Exception $e) {
        Log::error("Error validando documento para ID {$id}: " . $e->getMessage());
        // Continuar con el siguiente aunque falle uno
        continue;
      }
    }

    Log::info('Validación de documentos completada', [
      'total_procesados' => $processed,
      'validados' => $validated,
      'no_encontrados' => $notFound,
      'errados' => $errors
    ]);
  }

  /**
   * Valida un número de documento
   *
   * @param DocumentValidationService $service
   * @param string|null $numDoc
   * @return string 'VALIDADO' | 'NO_ENCONTRADO' | 'ERRADO'
   */
  private function validateDocument(DocumentValidationService $service, $numDoc)
  {
    // Si no hay número de documento, retornar ERRADO
    if (empty($numDoc)) {
      return 'ERRADO';
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
      // Si no tiene ni 8 ni 11 dígitos, retornar ERRADO
      return 'ERRADO';
    }

    try {
      // Validar el documento usando el servicio
      $result = $service->validateDocument(
        $documentType,
        $numDoc,
        [],
        true // usar cache
      );

      // Verificar si la validación fue exitosa
      if (isset($result['success']) && $result['success'] === true) {
        return 'VALIDADO';
      } else {
        return 'NO_ENCONTRADO';
      }
    } catch (Exception $e) {
      // Si hay un error en la validación, retornar NO_ENCONTRADO
      Log::warning("Error validando documento {$numDoc}: " . $e->getMessage());
      return 'NO_ENCONTRADO';
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