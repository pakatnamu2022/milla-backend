<?php

namespace App\Jobs;

use App\Http\Services\common\ImageCompressionService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\comercial\ApReceivingInspection;
use App\Models\ap\comercial\ApReceivingInspectionDamage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessReceivingInspectionImagesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    private const FILE_PATH_INSPECTION = '/ap/comercial/recepciones/inspecciones/';
    private const FILE_PATH_DAMAGE = '/ap/comercial/recepciones/inspecciones/danos/';

    /**
     * @param array $pendingImages Array de imágenes pendientes
     * @param array $compressionOptions Opciones de compresión
     * @param string $type 'inspection_photo' | 'damage_photo'
     */
    public function __construct(
        public array  $pendingImages,
        public array  $compressionOptions = [],
        public string $type = 'inspection_photo',
    ) {
        $this->onQueue('images-receiving-inspections');
    }

    public function handle(
        ImageCompressionService $compressionService,
        DigitalFileService      $digitalFileService
    ): void {
        foreach ($this->pendingImages as $imageData) {
            try {
                if ($this->type === 'damage_photo') {
                    $this->processDamageImage($imageData, $compressionService, $digitalFileService);
                } else {
                    $this->processInspectionImage($imageData, $compressionService, $digitalFileService);
                }
            } catch (Exception $e) {
                Log::error('Error processing receiving inspection image', [
                    'error' => $e->getMessage(),
                    'image_data' => $imageData,
                ]);
            }
        }
    }

    private function processInspectionImage(
        array                   $imageData,
        ImageCompressionService $compressionService,
        DigitalFileService      $digitalFileService
    ): void {
        $inspectionId = $imageData['receiving_inspection_id'];
        $photoType    = $imageData['photo_type'];
        $tempPath     = $imageData['temp_path'];
        $originalName = $imageData['original_name'] ?? 'inspection_photo.jpg';

        if (!file_exists($tempPath)) {
            return;
        }

        $inspection = ApReceivingInspection::find($inspectionId);
        if (!$inspection) {
            $this->cleanupTempFile($tempPath);
            return;
        }

        $uploadedFile = new UploadedFile($tempPath, $originalName, mime_content_type($tempPath), null, true);
        $options = array_merge(['quality' => 75, 'maxWidth' => 1920, 'maxHeight' => 1080], $this->compressionOptions);
        $compressedFile = $compressionService->compressToUploadedFile($uploadedFile, $options);

        $digitalFile = $digitalFileService->store($compressedFile, self::FILE_PATH_INSPECTION, 'public', $inspection->getTable());
        $inspection->update([$photoType . '_url' => $digitalFile->url]);

        $this->cleanupTempFile($tempPath);
    }

    private function processDamageImage(
        array                   $imageData,
        ImageCompressionService $compressionService,
        DigitalFileService      $digitalFileService
    ): void {
        $damageId     = $imageData['damage_id'];
        $tempPath     = $imageData['temp_path'];
        $originalName = $imageData['original_name'] ?? 'damage_photo.jpg';

        if (!file_exists($tempPath)) {
            return;
        }

        $damage = ApReceivingInspectionDamage::find($damageId);
        if (!$damage) {
            $this->cleanupTempFile($tempPath);
            return;
        }

        $uploadedFile = new UploadedFile($tempPath, $originalName, mime_content_type($tempPath), null, true);
        $options = array_merge(['quality' => 75, 'maxWidth' => 1920, 'maxHeight' => 1080], $this->compressionOptions);
        $compressedFile = $compressionService->compressToUploadedFile($uploadedFile, $options);

        $digitalFile = $digitalFileService->store($compressedFile, self::FILE_PATH_DAMAGE, 'public', $damage->getTable());
        $damage->update(['photo_url' => $digitalFile->url]);

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
        Log::error('ProcessReceivingInspectionImagesJob failed', [
            'error'        => $exception->getMessage(),
            'images_count' => count($this->pendingImages),
        ]);

        foreach ($this->pendingImages as $imageData) {
            if (isset($imageData['temp_path'])) {
                $this->cleanupTempFile($imageData['temp_path']);
            }
        }
    }
}
