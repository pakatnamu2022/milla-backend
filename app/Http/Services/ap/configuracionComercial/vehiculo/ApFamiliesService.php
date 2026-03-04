<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
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
    $family = ApFamilies::where('id', $id)->first();
    if (!$family) {
      throw new Exception('Familia no encontrado');
    }
    return $family;
  }

  public function store(mixed $data)
  {
    $marca = ApVehicleBrand::findOrFail($data['brand_id']);
    $data['code'] = $marca->codigo_dyn . $this->nextCorrelativeCount(
        ApFamilies::class,
        4,
        ['brand_id' => $data['brand_id']]
      );
    $family = ApFamilies::create($data);
    return new ApFamiliesResource($family);
  }

  public function show($id)
  {
    return new ApFamiliesResource($this->find($id));
  }

  public function completeBrandSeries($familyId): string
  {
    $family = $this->find($familyId);
    $familySeries = $family->code;
    $brandSeries = $family->brand->dyn_code;

    if (strlen($familySeries) === 4 && str_contains($familySeries, $brandSeries) && false) {
      return $familySeries;
    } else if (strlen($familySeries) === 2) {
      return $this->completeNumber($brandSeries . $familySeries, 4);
    } else {
      return $this->completeNumber(str($this->nextCorrelativeQuery(
        ApFamilies::where('brand_id', $family->brand_id),
        'code',
        2
      )), 4);
    }
  }

  public function update(mixed $data)
  {
    $family = $this->find($data['id']);
    $data['code'] = $this->completeBrandSeries($family->id);
    $family->update($data);
    return new ApFamiliesResource($family);
  }

  public function destroy($id)
  {
    $family = $this->find($id);
    DB::transaction(function () use ($family) {
      $family->delete();
    });
    return response()->json(['message' => 'Familia eliminado correctamente']);
  }
}
