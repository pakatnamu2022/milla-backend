<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleBrandResource;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionsistema\DigitalFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Exception;

class ApVehicleBrandService extends BaseService implements BaseServiceInterface
{
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos
  private const FILE_PATHS = [
    'logo' => '/ap/marcas/logos/',
    'logo_min' => '/ap/marcas/logos-min/'
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleBrand::class,
      $request,
      ApVehicleBrand::filters,
      ApVehicleBrand::sorts,
      ApVehicleBrandResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApVehicleBrand::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Marca de vehículo no encontrado');
    }
    return $engineType;
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      // Extraer archivos del array de datos
      $files = $this->extractFiles($data);

      // Crear la marca sin los archivos
      $brand = ApVehicleBrand::create($data);

      // Subir archivos y actualizar URLs
      if (!empty($files)) {
        $this->uploadAndAttachFiles($brand, $files);
      }

      DB::commit();
      return new ApVehicleBrandResource($brand);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show($id)
  {
    $brand = $this->find($id);
    return new ApVehicleBrandResource($brand);
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $brand = $this->find($data['id']);

      if ($brand->is_commercial != $data['is_commercial']) {
        throw new Exception('No puedes editar una marca que no corresponde al modulo respectivo.');
      }

      // Extraer archivos del array de datos
      $files = $this->extractFiles($data);

      // Actualizar datos de la marca
      $brand->update($data);

      // Si hay nuevos archivos, subirlos y actualizar URLs
      if (!empty($files)) {
        $this->uploadAndAttachFiles($brand, $files);
      }

      DB::commit();
      return new ApVehicleBrandResource($brand);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $brand = $this->find($id);

    DB::transaction(function () use ($brand) {
      // Eliminar archivos asociados si existen
      $this->deleteAttachedFiles($brand);

      // Eliminar la marca
      $brand->delete();
    });

    return response()->json(['message' => 'Marca de vehículo eliminado correctamente']);
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
  private function uploadAndAttachFiles($brand, array $files): void
  {
    foreach ($files as $field => $file) {
      $path = self::FILE_PATHS[$field];
      $model = $brand->getTable();

      // Subir archivo usando DigitalFileService
      $digitalFile = $this->digitalFileService->store($file, $path, 'public', $model);

      // Actualizar el campo del brand con la URL
      $brand->{$field} = $digitalFile->url;
    }

    $brand->save();
  }

  /**
   * Elimina archivos asociados al modelo
   */
  private function deleteAttachedFiles($brand): void
  {
    foreach (array_keys(self::FILE_PATHS) as $field) {
      if ($brand->{$field}) {
        // Buscar el archivo digital asociado y eliminarlo
        $digitalFile = DigitalFile::where('url', $brand->{$field})->first();

        if ($digitalFile) {
          $this->digitalFileService->destroy($digitalFile->id);
        }
      }
    }
  }
}
