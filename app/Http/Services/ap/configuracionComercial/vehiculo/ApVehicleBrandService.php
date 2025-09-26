<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleBrandResource;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Http\Services\BaseService;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HandlesFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Exception;

class ApVehicleBrandService extends BaseService implements BaseServiceInterface
{
  use HandlesFiles;

  // Configuración de campos de archivos
  private const FILE_FIELDS = [
    'logo' => [
      'directory' => 'brands/logos',
      'options' => ['max_size' => 2 * 1024 * 1024] // 2MB
    ],
    'logo_min' => [
      'directory' => 'brands/logos-min',
      'options' => ['max_size' => 1 * 1024 * 1024] // 1MB
    ]
  ];

  private const FILE_FIELD_NAMES = ['logo', 'logo_min'];

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

      // Validar archivos primero
      $this->validateFiles($data, self::FILE_FIELDS);

      // Procesar archivos públicos
      $processedData = $this->processMultipleFilesPublic($data, self::FILE_FIELDS);
      $brand = ApVehicleBrand::create($processedData);

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
    $resource = new ApVehicleBrandResource($brand);
    $resourceArray = $resource->toArray(request());

    // Generar URLs automáticamente
    $urls = $this->generateFileUrls($brand, self::FILE_FIELD_NAMES);
    $resourceArray = array_merge($resourceArray, $urls);

    return $resourceArray;
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $brand = $this->find($data['id']);

      // Validar archivos si los hay
      if ($this->hasFileUploads($data)) {
        $this->validateFiles($data, self::FILE_FIELDS);
      }

      // Detectar tipo de storage actual y procesar en consecuencia
      $isCurrentPublic = $this->isModelFilesPublic($brand);

      if ($isCurrentPublic) {
        $processedData = $this->processMultipleFilesPublic($data, self::FILE_FIELDS, $brand);
      } else {
        $processedData = $this->processMultipleFilesSecure($data, self::FILE_FIELDS, $brand);
      }

      $brand->update($processedData);

      DB::commit();
      return new ApVehicleBrandResource($brand);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Marca de vehículo eliminado correctamente']);
  }

  private function hasFileUploads(array $data): bool
  {
    foreach (self::FILE_FIELD_NAMES as $field) {
      if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
        return true;
      }
    }
    return false;
  }

  private function isModelFilesPublic($model): bool
  {
    foreach (self::FILE_FIELD_NAMES as $field) {
      if ($model->{$field}) {
        return $this->isFilePublic($model->{$field});
      }
    }
    return true;
  }
}
