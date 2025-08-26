<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApCommercialMastersResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApCommercialMasters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApCommercialMastersService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApCommercialMasters::class,
      $request,
      ApCommercialMasters::filters,
      ApCommercialMasters::sorts,
      ApCommercialMastersResource::class,
    );
  }

  public function find($id)
  {
    $ApCommercialMasters = ApCommercialMasters::where('id', $id)->first();
    if (!$ApCommercialMasters) {
      throw new Exception('Concepto de tabla maestra no encontrado');
    }
    return $ApCommercialMasters;
  }

  public function store(array $data)
  {
    $ApCommercialMasters = ApCommercialMasters::create($data);
    return new ApCommercialMastersResource($ApCommercialMasters);
  }

  public function show($id)
  {
    return new ApCommercialMastersResource($this->find($id));
  }

  public function update($data)
  {
    $ApCommercialMasters = $this->find($data['id']);
    $ApCommercialMasters->update($data);
    return new ApCommercialMastersResource($ApCommercialMasters);
  }

  public function destroy($id)
  {
    $ApCommercialMasters = $this->find($id);
    DB::transaction(function () use ($ApCommercialMasters) {
      $ApCommercialMasters->delete();
    });
    return response()->json(['message' => 'Concepto de tabla maestra eliminado correctamente']);
  }
}
