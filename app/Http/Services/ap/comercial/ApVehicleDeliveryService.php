<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApVehicleDeliveryResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\ApVehicleDelivery;
use Illuminate\Http\Request;
use Exception;

class ApVehicleDeliveryService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApVehicleDelivery::class,
      $request,
      ApVehicleDelivery::filters,
      ApVehicleDelivery::sorts,
      ApVehicleDeliveryResource::class,
    );
  }

  public function find($id)
  {
    $vehicleDelivery = ApVehicleDelivery::where('id', $id)->first();
    if (!$vehicleDelivery) {
      throw new Exception('Entrega de Vehículo no encontrado');
    }
    return $vehicleDelivery;
  }

  public function store(mixed $data)
  {
    $vehicleDelivery = ApVehicleDelivery::create($data);
    return new ApVehicleDeliveryResource($vehicleDelivery);
  }

  public function show($id)
  {
    return new ApVehicleDeliveryResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $vehicleDelivery = $this->find($data['id']);
    $vehicleDelivery->update($data);
    return new ApVehicleDeliveryResource($vehicleDelivery);
  }

  public function destroy($id)
  {
    $vehicleDelivery = $this->find($id);
    DB::transaction(function () use ($vehicleDelivery) {
      $vehicleDelivery->delete();
    });
    return response()->json(['message' => 'Entrega de Vehículo eliminada correctamente']);
  }
}
