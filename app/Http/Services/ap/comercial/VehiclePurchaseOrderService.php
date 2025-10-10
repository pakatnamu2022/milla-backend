<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiclePurchaseOrderService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      VehiclePurchaseOrder::class,
      $request,
      VehiclePurchaseOrder::filters,
      VehiclePurchaseOrder::sorts,
      VehiclePurchaseOrderResource::class
    );
  }

  public function find($id)
  {
    $vehiclePurchaseOrder = VehiclePurchaseOrder::where('id', $id)->first();
    if (!$vehiclePurchaseOrder) {
      throw new Exception('Orden de compra de vehículo no encontrada');
    }
    return $vehiclePurchaseOrder;
  }

  public function store(mixed $data)
  {
    $data['ap_vehicle_status_id'] = 28;
    $vehiclePurchaseOrder = VehiclePurchaseOrder::create($data);
    return new VehiclePurchaseOrderResource($vehiclePurchaseOrder);
  }

  public function show($id)
  {
    return new VehiclePurchaseOrderResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $vehiclePurchaseOrder = $this->find($data['id']);
    $vehiclePurchaseOrder->update($data);
    return new VehiclePurchaseOrderResource($vehiclePurchaseOrder);
  }

  public function destroy($id)
  {
    $vehiclePurchaseOrder = $this->find($id);
    DB::transaction(function () use ($vehiclePurchaseOrder) {
      $vehiclePurchaseOrder->delete();
    });
    return response()->json(['message' => 'Orden de compra de vehículo eliminada correctamente']);
  }
}
