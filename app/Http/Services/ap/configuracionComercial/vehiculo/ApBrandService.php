<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApBrandResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApBrand;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    $engineType = ApBrand::create($data);
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
}
