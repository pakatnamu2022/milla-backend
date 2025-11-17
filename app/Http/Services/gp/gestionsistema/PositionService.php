<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PositionService extends BaseService
{
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos en DigitalOcean
  private const FILE_PATHS = [
    'mof_adjunto' => '/ap/docs_cargo/',
    'fileadic1' => '/ap/docs_cargo/',
    'fileadic2' => '/ap/docs_cargo/',
    'fileadic3' => '/ap/docs_cargo/',
    'fileadic4' => '/ap/docs_cargo/',
    'fileadic5' => '/ap/docs_cargo/',
    'fileadic6' => '/ap/docs_cargo/',
  ];

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
    if (isset($cleanData['mof_adjunto']) && !($cleanData['mof_adjunto'] instanceof UploadedFile)) {
      unset($cleanData['mof_adjunto']);
    }

    if (isset($cleanData['files']) && !is_array($cleanData['files'])) {
      unset($cleanData['files']);
    }

    // Verificar si hay archivos para procesar
    $hasMofFile = isset($data['mof_adjunto']) && $data['mof_adjunto'] instanceof UploadedFile;
    $hasFiles = isset($data['files']) && is_array($data['files']) && count($data['files']) > 0;

    // Si no hay archivos para procesar, retornar los datos limpios
    if (!$hasMofFile && !$hasFiles) {
      unset($cleanData['files']);
      return $cleanData;
    }

    // Manejar el archivo MOF (requerido en store) - guardar en DigitalOcean
    if ($hasMofFile) {
      $mofFile = $this->digitalFileService->store(
        $data['mof_adjunto'],
        self::FILE_PATHS['mof_adjunto'],
        'public',
        'Position'
      );
      $cleanData['mof_adjunto'] = $mofFile->url ?? null;
    }

    // Manejar archivos adicionales (máximo 6) - guardar en DigitalOcean
    if ($hasFiles) {
      $fileFields = ['fileadic1', 'fileadic2', 'fileadic3', 'fileadic4', 'fileadic5', 'fileadic6'];

      // En update, buscar campos vacíos para llenar
      $availableFields = $fileFields;
      if ($position) {
        // Filtrar solo los campos que estén vacíos
        $availableFields = array_filter($fileFields, function ($field) use ($position) {
          return empty($position->$field);
        });
        $availableFields = array_values($availableFields);
      }

      // Guardar los archivos en los campos disponibles
      foreach ($data['files'] as $index => $file) {
        if ($file instanceof UploadedFile) {
          $fieldIndex = $position ? ($availableFields[$index] ?? null) : $fileFields[$index];

          if ($fieldIndex && $index < 6) {
            $uploadedFile = $this->digitalFileService->store(
              $file,
              self::FILE_PATHS[$fieldIndex],
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
      // Extraer hierarchical_category_id antes de procesar
      $hierarchicalCategoryId = $data['hierarchical_category_id'] ?? null;

      // Procesar archivos antes de crear la posición
      $processedData = $this->handleFileUploads($data);
      $processedData = $this->enrichPositionData($processedData);

      // Remover hierarchical_category_id de los datos ya que no pertenece a Position
      unset($processedData['hierarchical_category_id']);

      // Crear la posición
      $position = Position::create($processedData);

      // Sincronizar con HierarchicalCategoryDetail si se proporcionó hierarchical_category_id
      if ($hierarchicalCategoryId) {
        HierarchicalCategoryDetail::updateOrCreate(
          ['position_id' => $position->id],
          ['hierarchical_category_id' => $hierarchicalCategoryId]
        );
      }

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

      // Extraer hierarchical_category_id antes de procesar
      $hierarchicalCategoryId = $data['hierarchical_category_id'] ?? null;

      // Procesar archivos, pasando la posición existente para actualización
      $processedData = $this->handleFileUploads($data, $position);
      $processedData = $this->enrichPositionData($processedData);

      // Remover hierarchical_category_id de los datos ya que no pertenece a Position
      unset($processedData['hierarchical_category_id']);

      // Actualizar la posición
      $position->update($processedData);

      // Sincronizar con HierarchicalCategoryDetail
      if ($hierarchicalCategoryId) {
        HierarchicalCategoryDetail::updateOrCreate(
          ['position_id' => $position->id],
          ['hierarchical_category_id' => $hierarchicalCategoryId]
        );
      } else {
        // Si no se envía hierarchical_category_id, eliminar la relación existente
        HierarchicalCategoryDetail::where('position_id', $position->id)->delete();
      }

      DB::commit();
      return new PositionResource($position);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $position = $this->find($id);

      // Eliminar la relación con HierarchicalCategoryDetail
      HierarchicalCategoryDetail::where('position_id', $position->id)->delete();

      // Soft delete del position
      $position->status_deleted = 0;
      $position->save();

      DB::commit();
      return response()->json(['message' => 'Posición eliminada correctamente']);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
