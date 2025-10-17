<?php

namespace App\Http\Services\common;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    // storage/app/public/{directory}/{filename}
    $storedPath = Storage::disk('public')->putFileAs($directory, $file, $filename);
    $this->logFileOperation('store_public', $file, $storedPath);
    return ltrim(preg_replace('#^public/#', '', $storedPath), '/'); // <- normaliza
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
    $p = ltrim($path, '/');
    $p = preg_replace('#^(public|storage)/#', '', $p);
    if (Storage::disk('public')->exists($p)) {
      $ok = Storage::disk('public')->delete($p);
      $this->logFileOperation('delete_public', null, $p, $ok);
      return $ok;
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
    $p = ltrim($path, '/');
    // si viene como "/storage/dir/file", lo convertimos a "dir/file"
    $p = preg_replace('#^storage/#', '', $p);
    $p = preg_replace('#^public/#', '', $p);

    if (Storage::disk('public')->exists($p)) {
      return Storage::disk('public')->url($p); // APP_URL/storage/dir/file
    }
    return null;
  }

  /**
   * Obtiene la URL segura de un archivo privado
   */
  public function getSecureUrl(string $relativePath): string
  {
    // ejemplo: "contracts/123/file.pdf"
    return route('secure.file', ['path' => $relativePath]);
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

    // Log::info('File operation', $logData);
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
// 5) Mover de público -> privado (conserva estructura y evita duplicados)
  public function moveToSecure(string $publicPath): string
  {
    $src = ltrim($publicPath, '/');
    $src = preg_replace('#^(public|storage)/#', '', $src);
    if (!Storage::disk('public')->exists($src)) {
      throw new Exception('Archivo público no encontrado');
    }
    $dst = $src; // misma ruta relativa en disco 'private'
    if (Storage::disk('private')->exists($dst)) {
      // colisión: renombra destino
      $pi = pathinfo($dst);
      $dst = $pi['dirname'] . '/' . $pi['filename'] . '_' . bin2hex(random_bytes(4)) . '.' . $pi['extension'];
    }
    Storage::disk('private')->put($dst, Storage::disk('public')->get($src));
    Storage::disk('public')->delete($src);
    $this->logFileOperation('move_to_secure', null, $src . ' -> ' . $dst);
    return $dst; // <- relativo al disco private
  }

  /**
   * Mueve un archivo de privado a público
   */
// 6) Mover de privado -> público (análoga)
  public function moveToPublic(string $privatePath): string
  {
    $src = ltrim($privatePath, '/');
    if (!Storage::disk('private')->exists($src)) {
      throw new Exception('Archivo privado no encontrado');
    }
    $dst = $src; // misma ruta en disco 'public'
    if (Storage::disk('public')->exists($dst)) {
      $pi = pathinfo($dst);
      $dst = $pi['dirname'] . '/' . $pi['filename'] . '_' . bin2hex(random_bytes(4)) . '.' . $pi['extension'];
    }
    // asegurar directorio (solo crea carpeta; .htaccess no sirve en Nginx)
    $dir = dirname($dst);
    if ($dir !== '.' && !Storage::disk('public')->exists($dir)) {
      Storage::disk('public')->makeDirectory($dir);
    }
    Storage::disk('public')->put($dst, Storage::disk('private')->get($src));
    Storage::disk('private')->delete($src);
    $this->logFileOperation('move_to_public', null, $src . ' -> ' . $dst);
    return $dst;
  }

}
