<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemPolicyResource;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionsistema\DigitalFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class PerDiemPolicyService extends BaseService implements BaseServiceInterface
{
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos
  private const FILE_PATHS = [
    'document' => '/gh/viaticos/politicas/',
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemPolicy::query(),
      $request,
      PerDiemPolicy::filters ?? ['search' => ['name'], 'is_current' => '='],
      PerDiemPolicy::sorts ?? ['effective_from', 'name'],
      PerDiemPolicyResource::class,
    );
  }

  public function show(int $id)
  {
    return new PerDiemPolicyResource($this->find($id));
  }

  public function find($id)
  {
    $perDiemCategory = PerDiemPolicy::where('id', $id)->first();
    if (!$perDiemCategory) {
      throw new Exception('Política de viático no encontrada');
    }
    return $perDiemCategory;
  }

  public function store(mixed $data): PerDiemPolicy
  {
    try {
      DB::beginTransaction();

      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

      // Asignar created_by si no está presente
      if (!isset($data['created_by'])) {
        $data['created_by'] = auth()->id();
      }

      // Crear la política sin el archivo
      $policy = PerDiemPolicy::create($data);

      // Subir archivo y actualizar URL
      if (!empty($files)) {
        $this->uploadAndAttachFiles($policy, $files);
      }

      DB::commit();
      return $policy;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function update(mixed $data): PerDiemPolicy
  {
    try {
      DB::beginTransaction();

      $policy = $this->find($data['id']);

      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

      // Actualizar datos de la política
      $policy->update($data);

      // Si hay nuevo archivo, subirlo y actualizar URL
      if (!empty($files)) {
        // Eliminar archivo anterior si existe
        $this->deleteAttachedFiles($policy);

        // Subir nuevo archivo
        $this->uploadAndAttachFiles($policy, $files);
      }

      DB::commit();
      return $policy->fresh();
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): bool
  {
    $policy = PerDiemPolicy::findOrFail($id);

    if ($policy->is_current) {
      throw new Exception('No se puede eliminar la política activa.');
    }

    if ($policy->perDiemRequests()->exists()) {
      throw new Exception('No se puede eliminar la política porque tiene solicitudes asociadas.');
    }

    return DB::transaction(function () use ($policy) {
      // Eliminar archivos asociados si existen
      $this->deleteAttachedFiles($policy);

      // Eliminar la política
      return $policy->delete();
    });
  }

  /**
   * Extrae los archivos del array de datos
   */
  private function extractFiles(array &$data): array
  {
    $files = [];

    foreach (array_keys(self::FILE_PATHS) as $field) {
      if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
        $files[$field] = $data[$field];
        unset($data[$field]); // Remover del array para no guardarlo en la BD
      }
    }

    return $files;
  }

  /**
   * Sube archivos y actualiza el modelo con las URLs
   */
  private function uploadAndAttachFiles(PerDiemPolicy $policy, array $files): void
  {
    foreach ($files as $field => $file) {
      $path = self::FILE_PATHS[$field];
      $model = $policy->getTable();

      // Subir archivo usando DigitalFileService
      $digitalFile = $this->digitalFileService->store($file, $path, 'public', $model);

      // Actualizar el campo del policy con la URL
      $policy->document_path = $digitalFile->url;
    }

    $policy->save();
  }

  /**
   * Elimina archivos asociados al modelo
   */
  private function deleteAttachedFiles(PerDiemPolicy $policy): void
  {
    if ($policy->document_path) {
      // Buscar el archivo digital asociado y eliminarlo
      $digitalFile = DigitalFile::where('url', $policy->document_path)->first();

      if ($digitalFile) {
        $this->digitalFileService->destroy($digitalFile->id);
      }
    }
  }
}
