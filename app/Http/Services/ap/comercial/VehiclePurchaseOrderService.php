<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

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

  public function enrichData(mixed $data, $isCreate = true)
  {
    if ($isCreate) {
      $data['number'] =
        $this->nextCorrelativeQuery(Sede::where('id', $data['sede_id']), 'id', 2) .
        $this->nextCorrelativeCount(VehiclePurchaseOrder::class, 8, ['sede_id' => $data['sede_id']]);

      $data['number_guide'] =
        $this->nextCorrelativeQuery(Sede::where('id', $data['sede_id']), 'id', 2) .
        $this->nextCorrelativeCount(VehiclePurchaseOrder::class, 8, ['sede_id' => $data['sede_id']]);

      $data['ap_vehicle_status_id'] = ApVehicleStatus::PEDIDO_VN;
      $exchangeRateService = new ExchangeRateService();
      $data['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;
    }

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

    return $data;
  }

  /**
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): VehiclePurchaseOrderResource
  {
    $data = $this->enrichData($data);
    $vehiclePurchaseOrder = VehiclePurchaseOrder::create($data);
    $vehicleMovementService = new VehicleMovementService();
    $vehicleMovementService->storeRequestedVehicleMovement($vehiclePurchaseOrder->id);
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
      $data = $this->enrichData($data, false);
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
