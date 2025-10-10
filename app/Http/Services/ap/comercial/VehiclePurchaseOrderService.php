<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiclePurchaseOrderService extends BaseService implements BaseServiceInterface
{
  const DELETE = 'DELETE';

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

  public function enrichData(mixed $data)
  {
    $unit_price = round($data['unit_price'], 2);
    $discount = round($data['discount'], 2);
    $subtotal = round($unit_price - $discount, 2);
    if ($subtotal < 0) {
      throw new Exception('El subtotal no puede ser negativo');
    }
    $igv = round($subtotal * 0.18, 2);
    $total = round($subtotal + $igv, 2);

    $data['unit_price'] = $unit_price;
    $data['discount'] = $discount;
    $data['igv'] = $igv;
    $data['total'] = $total;
    $data['subtotal'] = $subtotal;
    $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;

    $exchangeRateService = new ExchangeRateService();
    $data['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;

    return $data;
  }

  public function store(mixed $data)
  {
    $data = $this->enrichData($data);
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

    if (isset($data['unit_price']) || isset($data['discount'])) {
      if (!isset($data['unit_price'])) {
        $data['unit_price'] = $vehiclePurchaseOrder->unit_price;
      }
      if (!isset($data['discount'])) {
        $data['discount'] = $vehiclePurchaseOrder->discount;
      }
      $data = $this->enrichData($data);
    }

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
