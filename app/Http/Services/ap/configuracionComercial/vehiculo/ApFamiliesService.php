<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApFamiliesService extends BaseService
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
    $ApCommercialMasters = ApFamilies::where('id', $id)->first();
    if (!$ApCommercialMasters) {
      throw new Exception('Familia no encontrado');
    }
    return $ApCommercialMasters;
  }

  public function store(array $data)
  {
    $marca = ApVehicleBrand::findOrFail($data['marca_id']);
    $data['codigo'] = $marca->codigo_dyn . $this->nextCorrelativeCount(
        ApFamilies::class,
        2,
        ['marca_id' => $data['marca_id']]
      );
    $ApCommercialMasters = ApFamilies::create($data);
    return new ApFamiliesResource($ApCommercialMasters);
  }

  public function show($id)
  {
    return new ApFamiliesResource($this->find($id));
  }

  public function update($data)
  {
    $ApCommercialMasters = $this->find($data['id']);
    $ApCommercialMasters->update($data);
    return new ApFamiliesResource($ApCommercialMasters);
  }

  public function destroy($id)
  {
    $ApCommercialMasters = $this->find($id);
    DB::transaction(function () use ($ApCommercialMasters) {
      $ApCommercialMasters->delete();
    });
    return response()->json(['message' => 'Familia eliminado correctamente']);
  }
}
