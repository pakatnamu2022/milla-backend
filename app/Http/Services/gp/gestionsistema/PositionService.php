<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PositionService extends BaseService
{
  protected DigitalFileService $digitalFileService;

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Position::where('status_deleted', 1),
      $request,
      Position::filters,
      Position::sorts,
      PositionResource::class,
    );
  }

  private function enrichPositionData(array $data): array
  {
    return $data;
  }

  private function handleFileUploads(array $data, ?Position $position = null): array
  {
    $cleanData = $data;

    // Remover mof_adjunto y files si no son archivos válidos
    if (isset($cleanData['mof_adjunto']) && !($cleanData['mof_adjunto'] instanceof \Illuminate\Http\UploadedFile)) {
      unset($cleanData['mof_adjunto']);
    }

    if (isset($cleanData['files']) && !is_array($cleanData['files'])) {
      unset($cleanData['files']);
    }

    // Verificar si hay archivos para procesar
    $hasMofFile = isset($data['mof_adjunto']) && $data['mof_adjunto'] instanceof \Illuminate\Http\UploadedFile;
    $hasFiles = isset($data['files']) && is_array($data['files']) && count($data['files']) > 0;

    // Si no hay archivos para procesar, retornar los datos limpios
    if (!$hasMofFile && !$hasFiles) {
      unset($cleanData['files']);
      return $cleanData;
    }

    // Crear el nombre de la carpeta basado en el nombre de la posición
    $positionName = $data['name'] ?? ($position ? $position->name : 'default');
    $folderPath = '/gp/position/' . $this->sanitizeFolderName($positionName) . '/';

    // Manejar el archivo MOF (requerido en store)
    if ($hasMofFile) {
      $mofFile = $this->digitalFileService->store(
        $data['mof_adjunto'],
        $folderPath,
        'public',
        'Position'
      );
      $cleanData['mof_adjunto'] = $mofFile->url ?? null;
    }

    // Manejar archivos adicionales (máximo 6)
    if ($hasFiles) {
      $fileFields = ['fileadic1', 'fileadic2', 'fileadic3', 'fileadic4', 'fileadic5', 'fileadic6'];

      // En update, buscar campos vacíos para llenar
      $availableFields = $fileFields;
      if ($position) {
        // Filtrar solo los campos que estén vacíos
        $availableFields = array_filter($fileFields, function($field) use ($position) {
          return empty($position->$field);
        });
        $availableFields = array_values($availableFields);
      }

      // Guardar los archivos en los campos disponibles
      foreach ($data['files'] as $index => $file) {
        if ($file instanceof \Illuminate\Http\UploadedFile) {
          $fieldIndex = $position ? ($availableFields[$index] ?? null) : $fileFields[$index];

          if ($fieldIndex && $index < 6) {
            $uploadedFile = $this->digitalFileService->store(
              $file,
              $folderPath,
              'public',
              'Position'
            );
            $cleanData[$fieldIndex] = $uploadedFile->url ?? null;
          }
        }
      }
    }

    // Remover el array de files
    unset($cleanData['files']);

    return $cleanData;
  }

  private function sanitizeFolderName(string $name): string
  {
    // Eliminar caracteres especiales y espacios, reemplazar con guiones
    $sanitized = preg_replace('/[^A-Za-z0-9\-_]/', '-', $name);
    $sanitized = preg_replace('/-+/', '-', $sanitized);
    return trim($sanitized, '-');
  }

  public function find($id)
  {
    $position = Position::where('id', $id)
      ->where('status_deleted', 1)->first();
    if (!$position) {
      throw new Exception('Posición no encontrada');
    }
    return $position;
  }

  public function store($data)
  {
    DB::beginTransaction();
    try {
      // Procesar archivos antes de crear la posición
      $processedData = $this->handleFileUploads($data);
      $processedData = $this->enrichPositionData($processedData);

      // Crear la posición
      $position = Position::create($processedData);

      DB::commit();
      return new PositionResource($position);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    return new PositionResource($this->find($id));
  }

  public function update($data)
  {
    DB::beginTransaction();
    try {
      $position = $this->find($data['id']);

      // Procesar archivos, pasando la posición existente para actualización
      $processedData = $this->handleFileUploads($data, $position);
      $processedData = $this->enrichPositionData($processedData);

      // Actualizar la posición
      $position->update($processedData);

      DB::commit();
      return new PositionResource($position);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $position = $this->find($id);
    $position->status_deleted = 0;
    $position->save();
    return response()->json(['message' => 'Posición eliminada correctamente']);
  }
}
