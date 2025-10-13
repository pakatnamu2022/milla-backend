<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\VehiclePurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\DatabaseSyncService;
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
    DB::beginTransaction();
    try {
      $data = $this->enrichData($data);
      $vehiclePurchaseOrder = VehiclePurchaseOrder::create($data);
      $vehicleMovementService = new VehicleMovementService();
      $vehicleMovementService->storeRequestedVehicleMovement($vehiclePurchaseOrder->id);

      // Validar y sincronizar antes de enviar la OC
      $this->validateAndSyncBeforeSending($vehiclePurchaseOrder);

      // Enviar la orden de compra
      $this->syncPurchaseOrder($vehiclePurchaseOrder);

      DB::commit();
      return new VehiclePurchaseOrderResource($vehiclePurchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Valida y sincroniza los datos antes de enviar la OC
   * @throws Exception
   */
  protected function validateAndSyncBeforeSending(VehiclePurchaseOrder $purchaseOrder): void
  {
    $syncService = new DatabaseSyncService();
    $resource = new VehiclePurchaseOrderResource($purchaseOrder);
    $resourceData = $resource->toArray(request());

    // 1. Validar que no existe la OC
    $existingPO = DB::connection('dbtp')
      ->table('neInTbOrdenCompra')
      ->where('OrdenCompraId', $purchaseOrder->number)
      ->first();

    if ($existingPO) {
      throw new Exception("La orden de compra {$purchaseOrder->number} ya existe en el sistema de destino");
    }

    // 2. Validar y sincronizar el proveedor
    $this->validateAndSyncSupplier($purchaseOrder, $syncService);

    // 3. Validar y sincronizar el artículo
    $this->validateAndSyncArticle($purchaseOrder, $syncService);
  }

  /**
   * Valida y sincroniza el proveedor
   * @throws Exception
   */
  protected function validateAndSyncSupplier(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $supplier = $purchaseOrder->supplier;

    if (!$supplier) {
      throw new Exception("No se encontró el proveedor asociado a la orden de compra");
    }

    // 2. Validar que existe el proveedor en la BD intermedia
    $existingSupplier = DB::connection('dbtp')
      ->table('neInTbProveedor')
      ->where('NumeroDocumento', $supplier->num_doc)
      ->first();

    // 2.1. Si no existe, enviar el proveedor Y su dirección juntos
    if (!$existingSupplier) {
      $syncService->sync('business_partners_ap_supplier', $supplier->toArray(), 'create');
      $syncService->sync('business_partners_directions_ap_supplier', $supplier->toArray(), 'create');
    } else {
      // Si existe pero tiene error, notificar
      if (!empty($existingSupplier->ProcesoError)) {
        \Illuminate\Support\Facades\Log::warning("El proveedor {$supplier->num_doc} tiene un error previo: {$existingSupplier->ProcesoError}");
      }
    }
  }

  /**
   * Valida y sincroniza el artículo (modelo)
   * @throws Exception
   */
  protected function validateAndSyncArticle(VehiclePurchaseOrder $purchaseOrder, DatabaseSyncService $syncService): void
  {
    $model = $purchaseOrder->model;

    if (!$model) {
      throw new Exception("No se encontró el modelo asociado a la orden de compra");
    }

    // 3. Validar que existe el artículo en la BD intermedia
    $existingArticle = DB::connection('dbtp')
      ->table('neInTbArticulo')
      ->where('Articulo', $model->code)
      ->first();

    // 3.1. Si no existe, enviar el artículo mediante job
    if (!$existingArticle) {
      \App\Jobs\SyncArticleJob::dispatch($model->id);
    } else {
      // Si existe pero tiene error, notificar
      if (!empty($existingArticle->ProcesoError)) {
        \Illuminate\Support\Facades\Log::warning("El artículo {$model->code} tiene un error previo: {$existingArticle->ProcesoError}");
      }
    }
  }

  /**
   * Sincroniza la orden de compra y programa los jobs de detalles
   * @throws Exception
   */
  protected function syncPurchaseOrder(VehiclePurchaseOrder $purchaseOrder): void
  {
    $syncService = new DatabaseSyncService();
    $resource = new VehiclePurchaseOrderResource($purchaseOrder);
    $resourceData = $resource->toArray(request());

    // 1. Enviar la OC Y su detalle juntos
    $syncService->sync('ap_vehicle_purchase_order', $resourceData, 'create');
    $syncService->sync('ap_vehicle_purchase_order_det', $resourceData, 'create');

    // 2. Crear un job para enviar la recepción (NI) con sus detalles
    // Este job validará que la OC esté en estado 1 antes de proceder
    \App\Jobs\SyncPurchaseOrderReceptionJob::dispatch($purchaseOrder->id);
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
