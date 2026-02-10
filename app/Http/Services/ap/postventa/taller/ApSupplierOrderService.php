<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApSupplierOrderResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
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

      // Calculate net_amount from details
      $netAmount = 0;
      if (!empty($details)) {
        foreach ($details as $detail) {
          $netAmount += $detail['total'] ?? 0;
        }
      }

      if ($netAmount <= 0) {
        throw new Exception('El monto neto de la orden de compra no puede ser cero.');
      }

      $data['net_amount'] = $netAmount;

      // Calculate tax_amount based on net_amount using VAT_TAX (18%)
      $data['tax_amount'] = round($netAmount * (Constants::VAT_TAX / 100), 2);

      // Calculate total_amount (net_amount + tax_amount)
      $data['total_amount'] = round($netAmount + $data['tax_amount'], 2);

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
      'requestDetails.orderPurchaseRequest.requestedBy.person',
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

      // Update details if provided and recalculate amounts
      if ($details !== null) {
        // Calculate new net_amount from details
        $netAmount = 0;
        foreach ($details as $detail) {
          $netAmount += $detail['total'] ?? 0;
        }
        $data['net_amount'] = $netAmount;

        // Calculate tax_amount based on net_amount using VAT_TAX (18%)
        $data['tax_amount'] = round($netAmount * (Constants::VAT_TAX / 100), 2);

        // Calculate total_amount (net_amount + tax_amount)
        $data['total_amount'] = round($netAmount + $data['tax_amount'], 2);

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
      // Get linked request detail IDs before deleting
      $requestDetailIds = $supplierOrder->requestDetails()->pluck('ap_order_purchase_request_details.id')->toArray();

      // Revert status of linked purchase request details back to 'pending'
      if (!empty($requestDetailIds)) {
        DB::table('ap_order_purchase_request_details')
          ->whereIn('id', $requestDetailIds)
          ->update(['status' => 'pending']);

        // Detach the relationship
        $supplierOrder->requestDetails()->detach();
      }

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
