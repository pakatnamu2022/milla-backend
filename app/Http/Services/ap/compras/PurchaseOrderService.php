<?php

namespace App\Http\Services\ap\compras;

use App\Http\Resources\ap\compras\PurchaseOrderDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderItemDynamicsResource;
use App\Http\Resources\ap\compras\PurchaseOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\ExportService;
use App\Http\Services\DatabaseSyncService;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\Sede;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseOrderService extends BaseService implements BaseServiceInterface
{
  protected ExportService $exportService;

  public function __construct(
    ExportService $exportService
  )
  {
    $this->exportService = $exportService;
  }

  /**
   * Exporta las órdenes de compra según los filtros proporcionados
   * @param Request $request
   * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, PurchaseOrder::class);
  }

  /**
   * Lista las órdenes de compra con filtros y paginación
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseOrder::class,
      $request,
      PurchaseOrder::filters,
      PurchaseOrder::sorts,
      PurchaseOrderResource::class
    );
  }

  /**
   * Busca una orden de compra por ID
   * @param $id
   * @return mixed
   * @throws Exception
   */
  public function find($id)
  {
    $purchaseOrder = PurchaseOrder::with(['items', 'supplier', 'warehouse'])->where('id', $id)->first();
    if (!$purchaseOrder) {
      throw new Exception('Orden de compra no encontrada');
    }
    return $purchaseOrder;
  }

  /**
   * Enriquece los datos de la orden de compra antes de crear o actualizar
   * @param mixed $data
   * @param $isCreate
   * @return mixed
   * @throws Exception
   */
  public function enrichData(mixed $data, $isCreate = true)
  {
    if ($isCreate) {
      // Generar número de OC correlativo
      $data['number'] = $this->nextCorrelativeCount(PurchaseOrder::class, 8, ['status' => true]);
      $data['number_guide'] = $this->nextCorrelativeCount(PurchaseOrder::class, 8, ['status' => true]);

      // Obtener tipo de cambio actual
      $exchangeRateService = new ExchangeRateService();
      $data['exchange_rate_id'] = $exchangeRateService->getCurrentUSDRate()->id;

      // Estado inicial de migración
      $data['migration_status'] = 'pending';
      $data['status'] = true;
    }

    // Calcular subtotal basado en los items
    $subtotal = 0;
    if (isset($data['items']) && is_array($data['items'])) {
      foreach ($data['items'] as $item) {
        $itemPrice = round($item['unit_price'] ?? 0, 2);
        $itemQuantity = $item['quantity'] ?? 1;
        $subtotal += $itemPrice * $itemQuantity;
      }
    }

    // Aplicar descuento
    $discount = round($data['discount'] ?? 0, 2);
    $subtotal = round($subtotal - $discount, 2);

    if ($subtotal < 0) {
      throw new Exception('El subtotal no puede ser negativo');
    }

    // Calcular ISC si aplica (10%)
    $isc = isset($data['has_isc']) && $data['has_isc'] ? round($subtotal * 0.10, 2) : 0;

    // Calcular IGV (18% sobre subtotal + ISC)
    $igv = round(($subtotal + $isc) * 0.18, 2);

    // Calcular total
    $total = round($subtotal + $isc + $igv, 2);

    $data['discount'] = $discount;
    $data['isc'] = $isc;
    $data['igv'] = $igv;
    $data['total'] = $total;
    $data['subtotal'] = $subtotal;

    return $data;
  }

  /**
   * Crea una nueva orden de compra
   * @throws Exception
   * @throws Throwable
   */
  public function store(mixed $data): PurchaseOrderResource
  {
    DB::beginTransaction();
    try {
      // Guardar items temporalmente
      $items = $data['items'] ?? [];

      // Enriquecer datos de la orden
      $data = $this->enrichData($data);

      // Crear la orden de compra
      $purchaseOrder = PurchaseOrder::create($data);

      // Guardar items si existen
      $this->saveItemsIfExists($items, $purchaseOrder);

      // Sincronizar con Dynamics si está habilitado
      if (config('database_sync.enabled', false)) {
        $this->syncPurchaseOrderToDynamics($purchaseOrder);
      }

      DB::commit();
      return new PurchaseOrderResource($purchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Sincroniza la orden de compra con Dynamics
   * @throws Exception
   */
  protected function syncPurchaseOrderToDynamics(PurchaseOrder $purchaseOrder): void
  {
    $syncService = new DatabaseSyncService();

    // Validar que existan items
    $purchaseOrder->load('items');
    if ($purchaseOrder->items->isEmpty()) {
      throw new Exception("La orden de compra debe tener al menos un ítem");
    }

    // Sincronizar cabecera de la orden
    $headerResource = new PurchaseOrderDynamicsResource($purchaseOrder);
    $headerData = $headerResource->toArray(request());
    $syncService->sync('ap_purchase_order', $headerData, 'create');

    // Sincronizar items de la orden
    $itemsResource = new PurchaseOrderItemDynamicsResource($purchaseOrder->items);
    $itemsData = $itemsResource->toArray(request());

    // Los items vienen como un array, cada uno debe sincronizarse
    foreach ($itemsData as $itemData) {
      $syncService->sync('ap_purchase_order_item', $itemData, 'create');
    }
  }

  /**
   * Muestra una orden de compra
   * @param $id
   * @return PurchaseOrderResource
   * @throws Exception
   */
  public function show($id)
  {
    return new PurchaseOrderResource($this->find($id));
  }

  /**
   * Actualiza una orden de compra
   * @param mixed $data
   * @return PurchaseOrderResource
   * @throws Throwable
   */
  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $purchaseOrder = $this->find($data['id']);

      // Guardar items temporalmente
      $items = $data['items'] ?? null;

      // Si se modifican items o precios, recalcular
      if ($items !== null || isset($data['discount'])) {
        // Cargar items actuales si no se pasaron nuevos
        if ($items === null) {
          $data['items'] = $purchaseOrder->items->map(function ($item) {
            return [
              'unit_measurement_id' => $item->unit_measurement_id,
              'description' => $item->description,
              'unit_price' => $item->unit_price,
              'quantity' => $item->quantity,
              'is_vehicle' => $item->is_vehicle,
            ];
          })->toArray();
        }

        $data = $this->enrichData($data, false);
      }

      // Actualizar la orden
      $purchaseOrder->update($data);

      // Actualizar items si se proporcionaron
      if ($items !== null) {
        // Eliminar items existentes
        $purchaseOrder->items()->delete();

        // Crear nuevos items
        $this->saveItemsIfExists($items, $purchaseOrder);
      }

      // Sincronizar con Dynamics si está habilitado y la orden está pendiente de migración
      if (config('database_sync.enabled', false) && $purchaseOrder->migration_status !== 'completed') {
        $this->syncPurchaseOrderToDynamics($purchaseOrder);
      }

      DB::commit();
      return new PurchaseOrderResource($purchaseOrder);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina una orden de compra
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   * @throws Throwable
   */
  public function destroy($id)
  {
    $purchaseOrder = $this->find($id);
    DB::transaction(function () use ($purchaseOrder) {
      // Eliminar items primero
      $purchaseOrder->items()->delete();
      // Eliminar la orden
      $purchaseOrder->delete();
    });
    return response()->json(['message' => 'Orden de compra eliminada correctamente']);
  }

  /**
   * @param mixed $items
   * @param $purchaseOrder
   * @return void
   */
  public function saveItemsIfExists(mixed $items, $purchaseOrder): void
  {
    if (!empty($items)) {
      foreach ($items as $itemData) {
        $unitPrice = round($itemData['unit_price'], 2);
        $quantity = $itemData['quantity'] ?? 1;
        $total = round($unitPrice * $quantity, 2);

        $purchaseOrder->items()->create([
          'unit_measurement_id' => $itemData['unit_measurement_id'],
          'description' => $itemData['description'],
          'unit_price' => $unitPrice,
          'quantity' => $quantity,
          'total' => $total,
          'is_vehicle' => $itemData['is_vehicle'] ?? false,
        ]);
      }
    }
  }

}
