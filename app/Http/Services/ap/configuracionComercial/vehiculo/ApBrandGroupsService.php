<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApBrandGroupsResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\vehiculo\ApBrandGroups;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApBrandGroupsService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApBrandGroups::class,
      $request,
      ApBrandGroups::filters,
      ApBrandGroups::sorts,
      ApBrandGroupsResource::class,
    );
  }

  public function find($id)
  {
    $engineType = ApBrandGroups::where('id', $id)->first();
    if (!$engineType) {
      throw new Exception('Grupo de marca no encontrado');
    }
    return $engineType;
  }

  public function store(array $data)
  {
    $engineType = ApBrandGroups::create($data);
    return new ApBrandGroupsResource($engineType);
  }

  public function show($id)
  {
    return new ApBrandGroupsResource($this->find($id));
  }

  public function update($data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new ApBrandGroupsResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Grupo de marca eliminado correctamente']);
  }
}
