<?php

namespace App\Http\Services\ap\postventa\repuestos;

use App\Http\Resources\ap\postventa\repuestos\ApprovedAccessoriesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\repuestos\ApprovedAccessories;
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
      \App\Http\Resources\ap\postventa\repuestos\ApprovedAccessoriesResource::class,
    );
  }

  public function find($id)
  {
    $ApprovedAccessories = ApprovedAccessories::where('id', $id)->first();
    if (!$ApprovedAccessories) {
      throw new Exception('Accesorio Homologado no encontrado');
    }
    return $ApprovedAccessories;
  }

  public function store(mixed $data)
  {
    $data['ap_vehicle_status_id'] = 28;
    $ApprovedAccessories = ApprovedAccessories::create($data);
    return new ApprovedAccessoriesResource($ApprovedAccessories);
  }

  public function show($id)
  {
    return new ApprovedAccessoriesResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $ApprovedAccessories = $this->find($data['id']);
    $ApprovedAccessories->update($data);
    return new \App\Http\Resources\ap\postventa\repuestos\ApprovedAccessoriesResource($ApprovedAccessories);
  }

  public function destroy($id)
  {
    $ApprovedAccessories = $this->find($id);
    DB::transaction(function () use ($ApprovedAccessories) {
      $ApprovedAccessories->delete();
    });
    return response()->json(['message' => 'Accesorio Homologado eliminado correctamente']);
  }
}
