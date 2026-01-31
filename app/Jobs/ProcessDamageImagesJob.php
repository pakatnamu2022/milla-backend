<?php

namespace App\Jobs;

use App\Http\Services\common\ImageCompressionService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\postventa\taller\ApVehicleInspection;
use App\Models\ap\postventa\taller\ApVehicleInspectionDamages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessDamageImagesJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 120;

  private const FILE_PATH_DAMAGE = '/ap/postventa/taller/inspecciones/danos/';
  private const FILE_PATH_INSPECTION = '/ap/postventa/taller/inspecciones/';

  /**
   * @param array $pendingImages Array de ['damage_id' => int, 'temp_path' => string]
   * @param array $compressionOptions Opciones de compresión
   */
  public function __construct(
    public array  $pendingImages,
    public array  $compressionOptions = [],
    public string $type = 'damage_photo',
  )
  {
    $this->onQueue('images-vehicle-inspections');
  }

  public function handle(
    ImageCompressionService $compressionService,
    DigitalFileService      $digitalFileService
  ): void
  {
    foreach ($this->pendingImages as $imageData) {
      try {
        if ($this->type === 'damage_photo') {
          $this->processImageDamage($imageData, $compressionService, $digitalFileService);
        } else {
          $this->processImageInspection($imageData, $compressionService, $digitalFileService);
        }
      } catch (Exception $e) {
        Log::error("Error processing images", [
          'error' => $e->getMessage(),
        ]);
      }
    }
  }

  private function processImageDamage(
    array                   $imageData,
    ImageCompressionService $compressionService,
    DigitalFileService      $digitalFileService
  ): void
  {
    $damageId = $imageData['damage_id'];
    $tempPath = $imageData['temp_path'];
    $originalName = $imageData['original_name'] ?? 'damage_photo.jpg';

    // Verificar que el archivo temporal existe
    if (!file_exists($tempPath)) {
      return;
    }

    // Verificar que el daño existe
    $damage = ApVehicleInspectionDamages::find($damageId);
    if (!$damage) {
      $this->cleanupTempFile($tempPath);
      return;
    }

    // Crear UploadedFile desde el archivo temporal
    $uploadedFile = new UploadedFile(
      $tempPath,
      $originalName,
      mime_content_type($tempPath),
      null,
      true
    );

    // Aplicar opciones de compresión por defecto si no se especificaron
    $options = array_merge([
      'quality' => 75,
      'maxWidth' => 1920,
      'maxHeight' => 1080,
    ], $this->compressionOptions);

    // Comprimir la imagen
    $compressedFile = $compressionService->compressToUploadedFile($uploadedFile, $options);

    // Subir al storage (S3/DigitalOcean)
    $model = $damage->getTable();
    $digitalFile = $digitalFileService->store($compressedFile, self::FILE_PATH_DAMAGE, 'public', $model);

    // Actualizar el daño con la URL
    $damage->update(['photo_url' => $digitalFile->url]);

    // Limpiar archivo temporal original
    $this->cleanupTempFile($tempPath);
  }

  private function processImageInspection(
    array                   $imageData,
    ImageCompressionService $compressionService,
    DigitalFileService      $digitalFileService
  ): void
  {
    $apVehicleInspectionId = $imageData['ap_vehicle_inspection_id'];
    $photoType = $imageData['photo_type']; // e.g., 'photo_front', 'photo_back', etc.
    $tempPath = $imageData['temp_path'];
    $originalName = $imageData['original_name'] ?? 'inspection_photo.jpg';

    // Verificar que el archivo temporal existe
    if (!file_exists($tempPath)) {
      return;
    }

    $vehicleInspection = ApVehicleInspection::find($apVehicleInspectionId);
    if (!$vehicleInspection) {
      $this->cleanupTempFile($tempPath);
      return;
    }

    // Crear UploadedFile desde el archivo temporal
    $uploadedFile = new UploadedFile(
      $tempPath,
      $originalName,
      mime_content_type($tempPath),
      null,
      true
    );

    // Aplicar opciones de compresión por defecto si no se especificaron
    $options = array_merge([
      'quality' => 75,
      'maxWidth' => 1920,
      'maxHeight' => 1080,
    ], $this->compressionOptions);

    // Comprimir la imagen
    $compressedFile = $compressionService->compressToUploadedFile($uploadedFile, $options);

    // Subir al storage (S3/DigitalOcean)
    $model = $vehicleInspection->getTable();
    $digitalFile = $digitalFileService->store($compressedFile, self::FILE_PATH_INSPECTION, 'public', $model);

    // Actualizar el daño con la URL
    $vehicleInspection->update([$photoType . '_url' => $digitalFile->url]);

    // Limpiar archivo temporal original
    $this->cleanupTempFile($tempPath);
  }

  private function cleanupTempFile(string $path): void
  {
    if (file_exists($path)) {
      @unlink($path);
    }
  }

  public function failed(Exception $exception): void
  {
    Log::error("ProcessDamageImagesJob failed", [
      'error' => $exception->getMessage(),
      'images_count' => count($this->pendingImages),
    ]);

    // Limpiar archivos temporales en caso de fallo
    foreach ($this->pendingImages as $imageData) {
      if (isset($imageData['temp_path'])) {
        $this->cleanupTempFile($imageData['temp_path']);
      }
    }
  }
}
