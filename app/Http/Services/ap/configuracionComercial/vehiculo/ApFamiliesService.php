<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApFamiliesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApFamilies::class,
      $request,
      ApFamilies::filters,
      ApFamilies::sorts,
      ApFamiliesResource::class,
    );
  }

  public function find($id)
  {
    $ApFamilies = ApFamilies::where('id', $id)->first();
    if (!$ApFamilies) {
      throw new Exception('Familia no encontrado');
    }
    return $ApFamilies;
  }

  public function store(mixed $data)
  {
    $marca = ApVehicleBrand::findOrFail($data['brand_id']);
    $data['code'] = $marca->codigo_dyn . $this->nextCorrelativeCount(
        ApFamilies::class,
        2,
        ['brand_id' => $data['brand_id']]
      );
    $ApFamilies = ApFamilies::create($data);
    return new ApFamiliesResource($ApFamilies);
  }

  public function show($id)
  {
    return new ApFamiliesResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $ApFamilies = $this->find($data['id']);
    $ApFamilies->update($data);
    return new ApFamiliesResource($ApFamilies);
  }

  public function destroy($id)
  {
    $ApFamilies = $this->find($id);
    DB::transaction(function () use ($ApFamilies) {
      $ApFamilies->delete();
    });
    return response()->json(['message' => 'Familia eliminado correctamente']);
  }
}
