<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApBrandResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApBrand;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApBrandService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApBrand::class,
      $request,
      ApBrand::filters,
      ApBrand::sorts,
      ApBrandResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApBrand::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Marca de vehículo no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $processedData = $this->processFiles($data);
    $engineType = ApBrand::create($processedData);
    return new ApBrandResource($engineType);
  }

  public function show($id)
  {
    return new ApBrandResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApBrandResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Marca de vehículo eliminado correctamente']);
  }

  /**
   * Procesa y guarda los archivos subidos
   */
  private function processFiles(array $data, ApBrand $existingBrand = null): array
  {
    $processedData = $data;

    if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {

      // Eliminar logo anterior si existe
      if ($existingBrand && $existingBrand->logo) {
        Storage::disk('public')->delete($existingBrand->logo);
      }

      $logoPath = $this->storeFile($data['logo'], 'brands/logos');
      $processedData['logo'] = $logoPath;

    } else {
      // Si no hay archivo nuevo en crear, setear como null
      if (!$existingBrand) {
        $processedData['logo'] = null;
      } else {
        $processedData['logo'] = $existingBrand->logo;
      }
    }

    // Procesar logo mini
    if (isset($data['logo_min']) && $data['logo_min'] instanceof \Illuminate\Http\UploadedFile) {

      // Eliminar logo_min anterior si existe
      if ($existingBrand && $existingBrand->logo_min) {
        Storage::disk('public')->delete($existingBrand->logo_min);
      }

      $logoMinPath = $this->storeFile($data['logo_min'], 'brands/logos-min');
      $processedData['logo_min'] = $logoMinPath;

    } else {
      // Si no hay archivo nuevo en crear, setear como null
      if (!$existingBrand) {
        $processedData['logo_min'] = null;
      } else {
        $processedData['logo_min'] = $existingBrand->logo_min;
      }
    }

    return $processedData;
  }

  /**
   * Guarda un archivo y retorna la ruta
   */
  private function storeFile(\Illuminate\Http\UploadedFile $file, string $directory): string
  {
    // Generar nombre único para el archivo
    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    // Guardar archivo
    $path = $file->storeAs($directory, $filename, 'public');

    return $path;
  }

  /**
   * Elimina archivos asociados a una marca
   */
  private function deleteFiles(ApBrand $brand): void
  {
    if ($brand->logo) {
      Storage::disk('public')->delete($brand->logo);
    }

    if ($brand->logo_min) {
      Storage::disk('public')->delete($brand->logo_min);
    }
  }
}
