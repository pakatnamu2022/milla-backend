<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApSupplierOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApSupplierOrder;
use App\Models\ap\postventa\taller\ApSupplierOrderDetails;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApSupplierOrderService extends BaseService implements BaseServiceInterface
{

  public function list(Request $request)
  {
    $query = ApSupplierOrder::query()->with([
      'supplier',
      'sede',
      'warehouse',
      'typeCurrency'
    ]);

    return $this->getFilteredResults(
      $query,
      $request,
      ApSupplierOrder::filters,
      ApSupplierOrder::sorts,
      ApSupplierOrderResource::class
    );
  }

  public function find($id)
  {
    $supplierOrder = ApSupplierOrder::with([
      'supplier',
      'sede',
      'warehouse',
      'typeCurrency',
      'createdBy',
      'details.product',
      'details.unitMeasurement'
    ])->where('id', $id)->first();

    if (!$supplierOrder) {
      throw new Exception('Orden de proveedor no encontrada');
    }

    return $supplierOrder;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Get exchange rate for order date
      $date = Carbon::parse($data['order_date'])->format('Y-m-d');

      $exchangeRate = ExchangeRate::where('date', $date)->first();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }
      $data['exchange_rate'] = $exchangeRate->rate;

      // Set created_by
      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      // Extract details from data
      $details = $data['details'] ?? [];
      unset($data['details']);

      // Calculate total_amount from details
      $totalAmount = 0;
      if (!empty($details)) {
        foreach ($details as $detail) {
          $totalAmount += $detail['total'] ?? 0;
        }
      }
      $data['total_amount'] = $totalAmount;

      // Create supplier order
      $supplierOrder = ApSupplierOrder::create($data);

      // OPCIONAL: Vincular solicitudes si se proporcionan
      if (isset($data['request_detail_ids']) && !empty($data['request_detail_ids'])) {
        $this->linkPurchaseRequests($supplierOrder, $data['request_detail_ids']);
      }

      // Create details
      if (!empty($details)) {
        foreach ($details as $detail) {
          $detail['ap_supplier_order_id'] = $supplierOrder->id;
          ApSupplierOrderDetails::create($detail);
        }
      }

      return new ApSupplierOrderResource($supplierOrder->load([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]));
    });
  }

  public function show($id)
  {
    $apSupplierOrder = $this->find($id);
    $apSupplierOrder->load([
      'supplier',
      'sede',
      'warehouse',
      'typeCurrency',
      'createdBy',
      'details.product',
    ]);
    return new ApSupplierOrderResource($apSupplierOrder);
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $supplierOrder = $this->find($data['id']);

      if (!is_null($supplierOrder->ap_purchase_order_id) || $supplierOrder->is_take) {
        throw new Exception('No se puede editar una orden al proveedor que ya se le ha registrado una factura.');
      }

      // Extract details from data
      $details = $data['details'] ?? null;
      unset($data['details']);

      // Update details if provided and recalculate total_amount
      if ($details !== null) {
        // Calculate new total_amount from details
        $totalAmount = 0;
        foreach ($details as $detail) {
          $totalAmount += $detail['total'] ?? 0;
        }
        $data['total_amount'] = $totalAmount;

        // Delete existing details
        ApSupplierOrderDetails::where('ap_supplier_order_id', $supplierOrder->id)->delete();

        // Create new details
        foreach ($details as $detail) {
          $detail['ap_supplier_order_id'] = $supplierOrder->id;
          ApSupplierOrderDetails::create($detail);
        }
      }

      // Update supplier order
      $supplierOrder->update($data);

      // Reload relations
      $supplierOrder->load([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]);

      return new ApSupplierOrderResource($supplierOrder);
    });
  }

  public function destroy($id)
  {
    $supplierOrder = $this->find($id);

    if (!is_null($supplierOrder->ap_purchase_order_id) || $supplierOrder->is_take) {
      throw new Exception('No se puede eliminar una orden al proveedor que ya se le ha registrado una factura.');
    }

    DB::transaction(function () use ($supplierOrder) {
      // Delete details (cascade will handle this, but explicit deletion is clearer)
      ApSupplierOrderDetails::where('ap_supplier_order_id', $supplierOrder->id)->delete();

      // Delete supplier order
      $supplierOrder->delete();
    });

    return response()->json(['message' => 'Orden de proveedor eliminada correctamente.']);
  }

  /**
   * Mark supplier order as taken
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function markAsTaken(int $id)
  {
    $supplierOrder = $this->find($id);

    $supplierOrder->update(['is_take' => true]);

    return response()->json([
      'message' => 'Orden marcada como tomada correctamente',
      'data' => new ApSupplierOrderResource($supplierOrder->fresh([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]))
    ]);
  }

  /**
   * Update supplier order status
   * @param int $id
   * @param string $status
   * @return \Illuminate\Http\JsonResponse
   */
  public function updateStatus(int $id, string $status)
  {
    $supplierOrder = $this->find($id);

    $allowedStatuses = ['pending', 'approved', 'rejected', 'completed'];

    if (!in_array($status, $allowedStatuses)) {
      throw new Exception("Estado no válido. Los estados permitidos son: " . implode(', ', $allowedStatuses));
    }

    $supplierOrder->update(['status' => $status]);

    return response()->json([
      'message' => 'Estado actualizado correctamente',
      'data' => new ApSupplierOrderResource($supplierOrder->fresh([
        'supplier',
        'sede',
        'warehouse',
        'typeCurrency',
        'createdBy',
        'details.product',
        'details.unitMeasurement'
      ]))
    ]);
  }

  /**
   * OPCIONAL: Vincular solicitudes de compra a la orden de compra
   * @param PurchaseOrder $apSupplierOrder
   * @param array $requestDetailIds IDs de ap_order_purchase_request_details
   * @return void
   * @throws Exception
   */
  protected function linkPurchaseRequests(ApSupplierOrder $apSupplierOrder, array $requestDetailIds): void
  {
    // Vincular los detalles de solicitud a la orden de compra
    $apSupplierOrder->requestDetails()->attach($requestDetailIds);

    // Actualizar el status de los detalles a 'ordered'
    DB::table('ap_order_purchase_request_details')
      ->whereIn('id', $requestDetailIds)
      ->update(['status' => 'ordered']);
  }
}
