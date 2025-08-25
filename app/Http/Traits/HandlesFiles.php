<?php

namespace App\Http\Traits;

use App\Http\Services\FileManagerService;
use Illuminate\Http\UploadedFile;

trait HandlesFiles
{
  protected FileManagerService $fileManager;

  protected function initFileManager(): void
  {
    if (!isset($this->fileManager)) {
      $this->fileManager = new FileManagerService();
    }
  }

  /**
   * Procesa un campo de archivo para storage público
   */
  protected function processFileFieldPublic(
    array  $data,
    string $fieldName,
    string $directory,
           $existingModel = null,
    array  $options = []
  ): ?string
  {
    $this->initFileManager();

    if (isset($data[$fieldName]) && $data[$fieldName] instanceof UploadedFile) {
      // Eliminar archivo anterior si existe
      if ($existingModel && $existingModel->{$fieldName}) {
        $this->fileManager->deleteFilePublic($existingModel->{$fieldName});
      }

      // Guardar nuevo archivo
      return $this->fileManager->storeFilePublic(
        $data[$fieldName],
        $directory,
        array_merge(['prefix' => $fieldName], $options)
      );
    }

    // Si no hay archivo nuevo, mantener el existente
    return $existingModel->{$fieldName} ?? null;
  }

  /**
   * Procesa un campo de archivo para storage seguro
   */
  protected function processFileFieldSecure(
    array  $data,
    string $fieldName,
    string $directory,
           $existingModel = null,
    array  $options = []
  ): ?string
  {
    $this->initFileManager();

    if (isset($data[$fieldName]) && $data[$fieldName] instanceof UploadedFile) {
      // Eliminar archivo anterior si existe
      if ($existingModel && $existingModel->{$fieldName}) {
        $this->fileManager->deleteFileSecurely($existingModel->{$fieldName});
      }

      // Guardar nuevo archivo
      return $this->fileManager->storeFileSecurely(
        $data[$fieldName],
        $directory,
        array_merge(['prefix' => $fieldName . '_secure'], $options)
      );
    }

    // Si no hay archivo nuevo, mantener el existente
    return $existingModel->{$fieldName} ?? null;
  }

  /**
   * Procesa múltiples campos de archivos públicos
   */
  protected function processMultipleFilesPublic(array $data, array $fileFields, $existingModel = null): array
  {
    $processedData = $data;

    foreach ($fileFields as $field => $config) {
      $directory = $config['directory'];
      $options = $config['options'] ?? [];

      $processedData[$field] = $this->processFileFieldPublic(
        $data,
        $field,
        $directory,
        $existingModel,
        $options
      );
    }

    return $processedData;
  }

  /**
   * Procesa múltiples campos de archivos seguros
   */
  protected function processMultipleFilesSecure(array $data, array $fileFields, $existingModel = null): array
  {
    $processedData = $data;

    foreach ($fileFields as $field => $config) {
      $directory = $config['directory'];
      $options = $config['options'] ?? [];

      $processedData[$field] = $this->processFileFieldSecure(
        $data,
        $field,
        $directory,
        $existingModel,
        $options
      );
    }

    return $processedData;
  }

  /**
   * Elimina archivos de un modelo (detecta automáticamente el tipo)
   */
  protected function deleteModelFiles($model, array $fileFields): void
  {
    $this->initFileManager();

    foreach ($fileFields as $field) {
      if ($model->{$field}) {
        $isPublic = $this->isFilePublic($model->{$field});

        if ($isPublic) {
          $this->fileManager->deleteFilePublic($model->{$field});
        } else {
          $this->fileManager->deleteFileSecurely($model->{$field});
        }
      }
    }
  }

  /**
   * Determina si un archivo está en storage público
   */
  protected function isFilePublic(string $filePath): bool
  {
    return str_contains($filePath, 'public/') ||
      !str_contains($filePath, 'private/');
  }

  /**
   * Genera URLs para archivos del modelo
   */
  protected function generateFileUrls($model, array $fileFields): array
  {
    $this->initFileManager();
    $urls = [];

    foreach ($fileFields as $field) {
      if ($model->{$field}) {
        $fieldName = $field . '_url';

        if ($this->isFilePublic($model->{$field})) {
          $urls[$fieldName] = $this->fileManager->getPublicUrl($model->{$field});
        } else {
          $urls['secure_' . $fieldName] = $this->fileManager->getSecureUrl($model->{$field});
        }
      }
    }

    return $urls;
  }

  /**
   * Migra archivos de un modelo de público a privado
   */
  protected function migrateModelFilesToSecure($model, array $fileFields): array
  {
    $this->initFileManager();
    $results = [];

    foreach ($fileFields as $field) {
      if ($model->{$field} && $this->isFilePublic($model->{$field})) {
        try {
          $newPath = $this->fileManager->moveToSecure($model->{$field});
          $model->{$field} = $newPath;
          $results[$field] = 'migrated';
        } catch (\Exception $e) {
          $results[$field] = 'failed: ' . $e->getMessage();
        }
      } else {
        $results[$field] = 'already_secure_or_empty';
      }
    }

    return $results;
  }

  /**
   * Migra archivos de un modelo de privado a público
   */
  protected function migrateModelFilesToPublic($model, array $fileFields): array
  {
    $this->initFileManager();
    $results = [];

    foreach ($fileFields as $field) {
      if ($model->{$field} && !$this->isFilePublic($model->{$field})) {
        try {
          $newPath = $this->fileManager->moveToPublic($model->{$field});
          $model->{$field} = $newPath;
          $results[$field] = 'migrated';
        } catch (\Exception $e) {
          $results[$field] = 'failed: ' . $e->getMessage();
        }
      } else {
        $results[$field] = 'already_public_or_empty';
      }
    }

    return $results;
  }

  /**
   * Valida archivos antes de procesarlos
   */
  protected function validateFiles(array $data, array $fileFields, array $globalOptions = []): void
  {
    $this->initFileManager();

    foreach ($fileFields as $field => $config) {
      if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
        $options = array_merge($globalOptions, $config['options'] ?? []);
        $this->fileManager->validateFile($data[$field], $options);
      }
    }
  }
}
