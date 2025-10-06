<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehicleVNResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\VehicleVN;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleVNService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      VehicleVN::class,
      $request,
      VehicleVN::filters,
      VehicleVN::sorts,
      VehicleVNResource::class,
    );
  }

  public function find($id)
  {
    $ApFamilies = VehicleVN::where('id', $id)->first();
    if (!$ApFamilies) {
      throw new Exception('Vehículo VN no encontrado');
    }
    return $ApFamilies;
  }

  public function store(mixed $data)
  {
    $engineType = VehicleVN::create($data);
    return new VehicleVNResource($engineType);
  }

  public function show($id)
  {
    return new VehicleVNResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $engineType = $this->find($data['id']);
    $engineType->update($data);
    return new VehicleVNResource($engineType);
  }

  public function destroy($id)
  {
    $engineType = $this->find($id);
    DB::transaction(function () use ($engineType) {
      $engineType->delete();
    });
    return response()->json(['message' => 'Vehículo VN eliminado correctamente']);
  }
}
