<?php

namespace App\Jobs;

use App\Imports\ap\postventa\VehicleLeakageEnrichment;
use App\Models\ap\postventa\VehicleLeakageJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVehicleLeakageFile implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * El número de veces que el job puede ser reintentado
   */
  public int $tries = 1;

  /**
   * El número de segundos que el job puede ejecutarse antes de timeout
   * 30 minutos para archivos grandes
   */
  public int $timeout = 1800;

  /**
   * El job que se está procesando
   */
  protected VehicleLeakageJob $jobRecord;

  /**
   * Create a new job instance.
   */
  public function __construct(public int $vehicleLeakageJobId)
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    try {
      // Cargar el registro del job
      $this->jobRecord = VehicleLeakageJob::findOrFail($this->vehicleLeakageJobId);

      Log::info("Iniciando procesamiento de job #{$this->jobRecord->id}", [
        'original_filename' => $this->jobRecord->original_filename,
        'job_id' => $this->jobRecord->id,
      ]);

      // Marcar como procesando
      $this->jobRecord->markAsProcessing();

      // Verificar que el archivo temporal existe
      if (!file_exists($this->jobRecord->temp_file_path)) {
        throw new \Exception("El archivo temporal no existe: {$this->jobRecord->temp_file_path}");
      }

      // Procesar el archivo
      $enrichment = new VehicleLeakageEnrichment();
      $results = $enrichment->process($this->jobRecord->temp_file_path);

      // Mover el archivo procesado a storage permanente
      $processedFileName = 'enriquecido_' . date('YmdHis') . '_' . $this->jobRecord->original_filename;
      $storagePath = 'vehicle_leakage_processed/' . $processedFileName;
      $fullStoragePath = storage_path('app/' . $storagePath);

      // Crear directorio si no existe
      $storageDir = dirname($fullStoragePath);
      if (!file_exists($storageDir)) {
        mkdir($storageDir, 0755, true);
      }

      // Copiar archivo procesado a storage permanente
      copy($this->jobRecord->temp_file_path, $fullStoragePath);

      Log::info("Archivo procesado exitosamente para job #{$this->jobRecord->id}", [
        'results' => $results,
        'processed_file_path' => $storagePath,
      ]);

      // Marcar como completado
      $this->jobRecord->markAsCompleted($storagePath, $results);

      // Eliminar archivo temporal
      @unlink($this->jobRecord->temp_file_path);

    } catch (\Exception $e) {
      Log::error("Error procesando job #{$this->vehicleLeakageJobId}: " . $e->getMessage(), [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      // Marcar como fallido
      if (isset($this->jobRecord)) {
        $this->jobRecord->markAsFailed($e->getMessage());

        // Eliminar archivo temporal
        if (file_exists($this->jobRecord->temp_file_path)) {
          @unlink($this->jobRecord->temp_file_path);
        }
      }

      // Re-lanzar la excepción para que Laravel marque el job como fallido
      throw $e;
    }
  }

  /**
   * El job falló al procesarse
   */
  public function failed(\Throwable $exception): void
  {
    Log::error("Job #{$this->vehicleLeakageJobId} falló definitivamente", [
      'exception' => $exception->getMessage(),
    ]);

    // Asegurar que el registro esté marcado como fallido
    if (isset($this->jobRecord)) {
      $this->jobRecord->markAsFailed($exception->getMessage());
    }
  }
}