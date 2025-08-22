<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileManagerService
{
  /**
   * Configuraciones por defecto
   */
  private const DEFAULT_MAX_SIZE = 5 * 1024 * 1024; // 5MB
  private const DEFAULT_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
  private const DEFAULT_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  /**
   * Guarda un archivo en storage público
   */
  public function storeFilePublic(UploadedFile $file, string $directory, array $options = []): string
  {
    $this->validateFile($file, $options);
    $filename = $this->generateSecureFilename($file, $options);

    // Asegurar protección del directorio público
    $this->ensurePublicDirectorySecurity($directory);

    $storedPath = Storage::disk('public')->putFileAs($directory, $file, $filename);

    $this->logFileOperation('store_public', $file, $storedPath);

    return $storedPath;
  }

  /**
   * Guarda un archivo en storage privado (seguro)
   */
  public function storeFileSecurely(UploadedFile $file, string $directory, array $options = []): string
  {
    $this->validateFile($file, $options);
    $filename = $this->generateSecureFilename($file, $options);

    $storedPath = Storage::disk('private')->putFileAs($directory, $file, $filename);

    $this->logFileOperation('store_private', $file, $storedPath);

    return $storedPath;
  }

  /**
   * Elimina un archivo público
   */
  public function deleteFilePublic(string $path): bool
  {
    if (Storage::disk('public')->exists($path)) {
      $deleted = Storage::disk('public')->delete($path);
      $this->logFileOperation('delete_public', null, $path, $deleted);
      return $deleted;
    }
    return false;
  }

  /**
   * Elimina un archivo privado
   */
  public function deleteFileSecurely(string $path): bool
  {
    if (Storage::disk('private')->exists($path)) {
      $deleted = Storage::disk('private')->delete($path);
      $this->logFileOperation('delete_private', null, $path, $deleted);
      return $deleted;
    }
    return false;
  }

  /**
   * Obtiene la URL pública de un archivo
   */
  public function getPublicUrl(string $path): ?string
  {
    if (Storage::disk('public')->exists($path)) {
      return Storage::disk('public')->url($path);
    }
    return null;
  }

  /**
   * Obtiene la URL segura de un archivo privado
   */
  public function getSecureUrl(string $filename): string
  {
    return route('secure.file', ['filename' => basename($filename)]);
  }

  /**
   * Valida un archivo subido
   */
  public function validateFile(UploadedFile $file, array $options = []): void
  {
    $maxSize = $options['max_size'] ?? self::DEFAULT_MAX_SIZE;
    $allowedExtensions = $options['allowed_extensions'] ?? self::DEFAULT_ALLOWED_EXTENSIONS;
    $allowedMimes = $options['allowed_mimes'] ?? self::DEFAULT_ALLOWED_MIMES;

    // Validar tamaño
    if ($file->getSize() > $maxSize) {
      $maxSizeMB = round($maxSize / (1024 * 1024), 1);
      throw new Exception("El archivo es demasiado grande. Máximo {$maxSizeMB}MB permitido.");
    }

    // Validar tipos MIME
    if (!in_array($file->getMimeType(), $allowedMimes)) {
      throw new Exception('Tipo de archivo no permitido. Solo se permiten: ' . implode(', ', $allowedMimes));
    }

    // Validar extensiones
    $extension = strtolower($file->getClientOriginalExtension());
    if (!in_array($extension, $allowedExtensions)) {
      throw new Exception('Extensión no permitida. Solo se permiten: ' . implode(', ', $allowedExtensions));
    }

    // Verificar que realmente sea una imagen (si es tipo imagen)
    if (str_starts_with($file->getMimeType(), 'image/')) {
      if (!getimagesize($file->getPathname())) {
        throw new Exception('El archivo no es una imagen válida.');
      }
    }
  }

  /**
   * Genera un nombre de archivo seguro y único
   */
  public function generateSecureFilename(UploadedFile $file, array $options = []): string
  {
    $prefix = $options['prefix'] ?? '';
    $extension = $file->getClientOriginalExtension();
    $timestamp = time();
    $random = bin2hex(random_bytes(16));
    $userId = auth()->id() ?? 'anonymous';

    $filename = $prefix ?
      "{$prefix}_{$timestamp}_{$userId}_{$random}.{$extension}" :
      "{$timestamp}_{$userId}_{$random}.{$extension}";

    return $filename;
  }

  /**
   * Crea archivo .htaccess para proteger directorios públicos
   */
  private function ensurePublicDirectorySecurity(string $directory): void
  {
    $htaccessPath = storage_path("app/public/{$directory}/.htaccess");
    $htaccessDir = dirname($htaccessPath);

    // Crear directorio si no existe
    if (!is_dir($htaccessDir)) {
      mkdir($htaccessDir, 0755, true);
    }
  }

  /**
   * Registra operaciones de archivos para auditoría
   */
  private function logFileOperation(string $operation, ?UploadedFile $file, string $path, ?bool $success = true): void
  {
    $logData = [
      'operation' => $operation,
      'path' => $path,
      'success' => $success,
      'user_id' => auth()->id(),
      'ip' => request()->ip(),
    ];

    if ($file) {
      $logData['original_name'] = $file->getClientOriginalName();
      $logData['size'] = $file->getSize();
      $logData['mime_type'] = $file->getMimeType();
    }

    Log::info('File operation', $logData);
  }

  /**
   * Obtiene información de un archivo
   */
  public function getFileInfo(string $path, string $disk = 'public'): array
  {
    if (!Storage::disk($disk)->exists($path)) {
      throw new Exception('Archivo no encontrado');
    }

    return [
      'path' => $path,
      'size' => Storage::disk($disk)->size($path),
      'last_modified' => Storage::disk($disk)->lastModified($path),
      'mime_type' => Storage::disk($disk)->mimeType($path),
      'url' => $disk === 'public' ? Storage::disk($disk)->url($path) : null,
    ];
  }

  /**
   * Mueve un archivo de público a privado
   */
  public function moveToSecure(string $publicPath): string
  {
    if (!Storage::disk('public')->exists($publicPath)) {
      throw new Exception('Archivo público no encontrado');
    }

    // Leer contenido del archivo público
    $content = Storage::disk('public')->get($publicPath);

    // Generar nueva ruta para archivo privado
    $filename = basename($publicPath);
    $directory = dirname($publicPath);
    $privatePath = "{$directory}/{$filename}";

    // Guardar en storage privado
    Storage::disk('private')->put($privatePath, $content);

    // Eliminar archivo público
    Storage::disk('public')->delete($publicPath);

    $this->logFileOperation('move_to_secure', null, $publicPath . ' -> ' . $privatePath);

    return $privatePath;
  }

  /**
   * Mueve un archivo de privado a público
   */
  public function moveToPublic(string $privatePath): string
  {
    if (!Storage::disk('private')->exists($privatePath)) {
      throw new Exception('Archivo privado no encontrado');
    }

    // Leer contenido del archivo privado
    $content = Storage::disk('private')->get($privatePath);

    // Generar nueva ruta para archivo público
    $filename = basename($privatePath);
    $directory = dirname($privatePath);
    $publicPath = "{$directory}/{$filename}";

    // Asegurar protección del directorio
    $this->ensurePublicDirectorySecurity($directory);

    // Guardar en storage público
    Storage::disk('public')->put($publicPath, $content);

    // Eliminar archivo privado
    Storage::disk('private')->delete($privatePath);

    $this->logFileOperation('move_to_public', null, $privatePath . ' -> ' . $publicPath);

    return $publicPath;
  }
}
