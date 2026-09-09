<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\SupplyPhotoResource;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\tp\comercial\SupplyControl;
use App\Models\tp\comercial\SupplyPhoto;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplyPhotoService extends BaseService
{
    protected DigitalFileService $digitalFileService;

    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
    ];

    private int $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct(DigitalFileService $digitalFileService)
    {
        $this->digitalFileService = $digitalFileService;
    }

    public function storePhotoFromBase64(
        string $base64Image,
        int $supplyControlId,
        ?string $fileName = null,
        ?string $photoType = 'ticket'
    ): SupplyPhoto {
        $temp = null;
        try {
            if (strpos($base64Image, 'base64,') !== false) {
                $base64Image = explode('base64,', $base64Image)[1];
            }

            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                throw new Exception('Base64 inválido');
            }

            $tempFile = tmpfile();
            fwrite($tempFile, $imageData);
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];

            $extension = $this->detectExtension($imageData);
            $uploadedFile = new UploadedFile(
                $tempFilePath,
                $fileName ?? "supply_{$supplyControlId}_{$photoType}_".time().".{$extension}",
                $this->detectMimeType($imageData),
                null,
                true
            );

            $result = $this->storePhoto($uploadedFile, $supplyControlId, $photoType);

            if ($tempFile) {
                fclose($tempFile);
            }

            return $result;

        } catch (Throwable $th) {
            throw new Exception('Error al procesar la imagen: '.$th->getMessage());
        }
    }

    public function storePhoto(UploadedFile $file, int $supplyControlId, string $photoType = 'ticket'): SupplyPhoto
    {
        DB::beginTransaction();
        try {
            $this->validateFile($file);

            $supplyControl = SupplyControl::where('status_deleted', 1)
                ->findOrFail($supplyControlId);

            $path = $this->generateFilePath($file, $supplyControlId);

            $digitalFile = $this->digitalFileService->store(
                $file,
                $path,
                'public',
                'op_supply_photo'
            );

            $photo = SupplyPhoto::create([
                'supply_control_id' => $supplyControlId,
                'digital_file_id' => $digitalFile->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'photo_type' => $photoType ?? 'ticket',
                'uploaded_at' => now(),
                'uploaded_by' => auth()->id(),
                'status_deleted' => 1,
            ]);

            if ($photoType === 'ticket') {
                $supplyControl->update(['photo_id' => $photo->id]);
            }

            DB::commit();

            return $photo;

        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplyPhotoService@storePhoto: '.$th->getMessage());
            throw new Exception('Error al guardar la foto: '.$th->getMessage());
        }
    }

    public function show($id): SupplyPhotoResource
    {
        $photo = SupplyPhoto::with('digitalFile')
            ->where('status_deleted', 1)
            ->findOrFail($id);

        return new SupplyPhotoResource($photo);
    }

    public function destroy($id): array
    {
        DB::beginTransaction();
        try {
            $photo = SupplyPhoto::where('status_deleted', 1)->findOrFail($id);

            if ($photo->digital_file_id) {
                $this->digitalFileService->destroy($photo->digital_file_id);
            }

            $photo->update(['status_deleted' => 0]);

            if ($photo->photo_type === 'ticket') {
                SupplyControl::where('photo_id', $id)->update(['photo_id' => null]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Foto eliminada correctamente',
                'photo_id' => $id,
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplyPhotoService@destroy: '.$th->getMessage());
            throw new Exception('Error al eliminar la foto: '.$th->getMessage());
        }
    }

    private function validateFile(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception('Tipo de archivo no permitido. Permitidos: '.implode(', ', $this->allowedMimeTypes));
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception('El archivo es demasiado grande. Máximo '.($this->maxFileSize / 1024 / 1024).'MB');
        }
    }

    private function generateFilePath(UploadedFile $file, int $supplyControlId): string
    {
        $year = date('Y');
        $month = date('m');
        $timestamp = time();
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = "supply_{$supplyControlId}_{$timestamp}.{$extension}";

        return "tp/supply_photos/{$year}/{$month}/{$supplyControlId}/{$fileName}";
    }

    private function detectExtension(string $data): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        return match ($mimeType) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/jpeg', 'image/jpg' => 'jpg',
            'application/pdf' => 'pdf',
            default => 'jpg',
        };
    }

    private function detectMimeType(string $data): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        return $mimeType ?: 'image/jpeg';
    }
}
