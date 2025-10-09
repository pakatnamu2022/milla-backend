<?php

namespace App\Http\Services\ap\postventa;

use App\Http\Resources\ap\postventa\ApprovedAccessoriesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\ApprovedAccessories;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovedAccessoriesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApprovedAccessories::class,
      $request,
      ApprovedAccessories::filters,
      ApprovedAccessories::sorts,
      ApprovedAccessoriesResource::class,
    );
  }

  public function find($id)
  {
    $ApFamilies = ApprovedAccessories::where('id', $id)->first();
    if (!$ApFamilies) {
      throw new Exception('Accesorio Homologado no encontrado');
    }
    return $ApFamilies;
  }

  public function store(mixed $data)
  {
    $data['ap_vehicle_status_id'] = 28;
    $engineType = ApprovedAccessories::create($data);
    return new ApprovedAccessoriesResource($engineType);
  }

  public function show($id)
  {
    return new ApprovedAccessoriesResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApprovedAccessories($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Accesorio Homologado eliminado correctamente']);
  }
}
