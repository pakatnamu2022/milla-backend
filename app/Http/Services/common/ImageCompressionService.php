<?php

namespace App\Http\Services\common;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Interfaces\EncodedImageInterface;

/**
 * Servicio modular para compresión y optimización de imágenes.
 * Puede ser utilizado en cualquier parte de la aplicación.
 */
class ImageCompressionService
{
  // Configuración por defecto
  private const DEFAULT_QUALITY = 75;
  private const DEFAULT_MAX_WIDTH = 1920;
  private const DEFAULT_MAX_HEIGHT = 1080;

  /**
   * Comprime una imagen con las opciones especificadas.
   *
   * @param UploadedFile|string $image Archivo de imagen o ruta del archivo
   * @param array $options Opciones de compresión:
   *   - quality: int (1-100) Calidad de compresión (default: 75)
   *   - maxWidth: int Ancho máximo en pixeles (default: 1920)
   *   - maxHeight: int Alto máximo en pixeles (default: 1080)
   *   - format: string Formato de salida: 'jpg', 'png', 'webp' (default: mantiene original)
   *   - maintainAspectRatio: bool Mantener proporción al redimensionar (default: true)
   * @return array ['content' => string, 'extension' => string, 'mimeType' => string]
   */
  public function compress(UploadedFile|string $image, array $options = []): array
  {
    $quality = $options['quality'] ?? self::DEFAULT_QUALITY;
    $maxWidth = $options['maxWidth'] ?? self::DEFAULT_MAX_WIDTH;
    $maxHeight = $options['maxHeight'] ?? self::DEFAULT_MAX_HEIGHT;
    $format = $options['format'] ?? null;
    $maintainAspectRatio = $options['maintainAspectRatio'] ?? true;

    // Cargar la imagen
    $img = $image instanceof UploadedFile
      ? Image::read($image->getRealPath())
      : Image::read($image);

    // Redimensionar si excede los límites
    $width = $img->width();
    $height = $img->height();

    if ($width > $maxWidth || $height > $maxHeight) {
      if ($maintainAspectRatio) {
        $img = $img->scaleDown($maxWidth, $maxHeight);
      } else {
        $img = $img->resize($maxWidth, $maxHeight);
      }
    }

    // Determinar formato de salida
    $outputFormat = $format ?? $this->getFormatFromImage($image);
    $encoded = $this->encodeImage($img, $outputFormat, $quality);

    return [
      'content' => $encoded->toString(),
      'extension' => $outputFormat,
      'mimeType' => $this->getMimeType($outputFormat),
    ];
  }

  /**
   * Comprime una imagen y la guarda en un archivo temporal.
   *
   * @param UploadedFile|string $image
   * @param array $options
   * @return string Ruta del archivo temporal
   */
  public function compressToTempFile(UploadedFile|string $image, array $options = []): string
  {
    $compressed = $this->compress($image, $options);

    $tempPath = sys_get_temp_dir() . '/' . uniqid('img_') . '.' . $compressed['extension'];
    file_put_contents($tempPath, $compressed['content']);

    return $tempPath;
  }

  /**
   * Comprime una imagen y retorna un nuevo UploadedFile.
   *
   * @param UploadedFile $image
   * @param array $options
   * @return UploadedFile
   */
  public function compressToUploadedFile(UploadedFile $image, array $options = []): UploadedFile
  {
    $compressed = $this->compress($image, $options);

    // Generar nombre único
    $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
    $newName = $originalName . '.' . $compressed['extension'];

    // Crear archivo temporal
    $tempPath = sys_get_temp_dir() . '/' . uniqid('img_') . '.' . $compressed['extension'];
    file_put_contents($tempPath, $compressed['content']);

    return new UploadedFile(
      $tempPath,
      $newName,
      $compressed['mimeType'],
      null,
      true
    );
  }

  /**
   * Comprime múltiples imágenes.
   *
   * @param array $images Array de UploadedFile
   * @param array $options
   * @return array Array de UploadedFile comprimidos
   */
  public function compressMany(array $images, array $options = []): array
  {
    $compressed = [];

    foreach ($images as $key => $image) {
      if ($image instanceof UploadedFile && $this->isImage($image)) {
        $compressed[$key] = $this->compressToUploadedFile($image, $options);
      } else {
        $compressed[$key] = $image;
      }
    }

    return $compressed;
  }

  /**
   * Verifica si el archivo es una imagen válida.
   */
  public function isImage(UploadedFile $file): bool
  {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($file->getMimeType(), $allowedMimes);
  }

  /**
   * Obtiene el tamaño estimado de compresión.
   *
   * @param UploadedFile $image
   * @param array $options
   * @return array ['original' => int, 'compressed' => int, 'saved' => int, 'percentage' => float]
   */
  public function getCompressionStats(UploadedFile $image, array $options = []): array
  {
    $originalSize = $image->getSize();
    $compressed = $this->compress($image, $options);
    $compressedSize = strlen($compressed['content']);

    return [
      'original' => $originalSize,
      'compressed' => $compressedSize,
      'saved' => $originalSize - $compressedSize,
      'percentage' => round((($originalSize - $compressedSize) / $originalSize) * 100, 2),
    ];
  }

  /**
   * Codifica la imagen en el formato especificado.
   */
  private function encodeImage($img, string $format, int $quality): EncodedImageInterface
  {
    return match ($format) {
      'webp' => $img->toWebp($quality),
      'png' => $img->toPng(),
      'gif' => $img->toGif(),
      default => $img->toJpeg($quality),
    };
  }

  /**
   * Obtiene el formato de la imagen original.
   */
  private function getFormatFromImage(UploadedFile|string $image): string
  {
    if ($image instanceof UploadedFile) {
      $mime = $image->getMimeType();
    } else {
      $mime = mime_content_type($image);
    }

    return match ($mime) {
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/webp' => 'webp',
      default => 'jpg',
    };
  }

  /**
   * Obtiene el MIME type según el formato.
   */
  private function getMimeType(string $format): string
  {
    return match ($format) {
      'png' => 'image/png',
      'gif' => 'image/gif',
      'webp' => 'image/webp',
      default => 'image/jpeg',
    };
  }
}