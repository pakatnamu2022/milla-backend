<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderPurchaseRequestsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequestDetails;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderPurchaseRequestsService extends BaseService implements BaseServiceInterface
{

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApOrderPurchaseRequests::class,
      $request,
      ApOrderPurchaseRequests::filters,
      ApOrderPurchaseRequests::sorts,
      ApOrderPurchaseRequestsResource::class
    );
  }

  public function find($id)
  {
    $purchaseRequest = ApOrderPurchaseRequests::with([
      'apOrderQuotation',
      'purchaseOrder',
      'warehouse',
      'details.product'
    ])->where('id', $id)->first();

    if (!$purchaseRequest) {
      throw new Exception('Solicitud de compra no encontrada');
    }

    return $purchaseRequest;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Generate unique request number
      $data['request_number'] = $this->generateRequestNumber();

      // Set created_by
      if (auth()->check()) {
        $data['created_by'] = auth()->user()->id;
      }

      // Extract details from data
      $details = $data['details'] ?? [];
      unset($data['details']);

      // Validate that all products exist in the warehouse
      if (!empty($details) && isset($data['warehouse_id'])) {
        $this->validateProductsInWarehouse($details, $data['warehouse_id']);
      }

      // Mark quotation as taken if provided
      if (isset($data['ap_order_quotation_id'])) {
        ApOrderQuotations::find($data['ap_order_quotation_id'])
          ?->markAsTaken();
      }

      // Create purchase request
      $purchaseRequest = ApOrderPurchaseRequests::create($data);

      // Create details
      if (!empty($details)) {
        foreach ($details as $detail) {
          $detail['order_purchase_request_id'] = $purchaseRequest->id;
          ApOrderPurchaseRequestDetails::create($detail);
        }
      }

      return new ApOrderPurchaseRequestsResource($purchaseRequest->load([
        'apOrderQuotation',
        'purchaseOrder',
        'warehouse',
        'details.product'
      ]));
    });
  }

  public function show($id)
  {
    return new ApOrderPurchaseRequestsResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $purchaseRequest = $this->find($data['id']);

      // Extract details from data
      $details = $data['details'] ?? null;
      unset($data['details']);

      // Validate that all products exist in the warehouse if details are being updated
      if ($details !== null && isset($data['warehouse_id'])) {
        $this->validateProductsInWarehouse($details, $data['warehouse_id']);
      } elseif ($details !== null) {
        // If warehouse_id is not in data, use the current warehouse_id
        $this->validateProductsInWarehouse($details, $purchaseRequest->warehouse_id);
      }

      // Update purchase request
      $purchaseRequest->update($data);

      // Update details if provided
      if ($details !== null) {
        // Delete existing details
        ApOrderPurchaseRequestDetails::where('order_purchase_request_id', $purchaseRequest->id)->delete();

        // Create new details
        foreach ($details as $detail) {
          $detail['order_purchase_request_id'] = $purchaseRequest->id;
          ApOrderPurchaseRequestDetails::create($detail);
        }
      }

      // Reload relations
      $purchaseRequest->load([
        'apOrderQuotation',
        'purchaseOrder',
        'warehouse',
        'details.product'
      ]);

      return new ApOrderPurchaseRequestsResource($purchaseRequest);
    });
  }

  public function destroy($id)
  {
    $purchaseRequest = $this->find($id);

    DB::transaction(function () use ($purchaseRequest) {
      // Delete details (cascade will handle this, but explicit deletion is clearer)
      ApOrderPurchaseRequestDetails::where('order_purchase_request_id', $purchaseRequest->id)->delete();

      // Delete purchase request
      $purchaseRequest->delete();
    });

    return response()->json(['message' => 'Solicitud de compra eliminada correctamente.']);
  }

  /**
   * Validate that all products exist in the specified warehouse
   *
   * @param array $details Array of product details
   * @param int $warehouseId Warehouse ID
   * @throws Exception if any product is not registered in the warehouse
   */
  private function validateProductsInWarehouse(array $details, int $warehouseId): void
  {
    foreach ($details as $detail) {
      $productId = $detail['product_id'] ?? null;

      if (!$productId) {
        continue;
      }

      // Check if product exists in warehouse stock (regardless of quantity)
      $productStock = ProductWarehouseStock::where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->first();

      if (!$productStock) {
        // Get product name for better error message
        $product = Products::find($productId);
        $productName = $product ? $product->name : "ID: {$productId}";

        throw new Exception(
          "El producto '{$productName}' no está registrado en este almacén. " .
          "Por favor, registre el producto en el almacén antes de crear la solicitud de compra."
        );
      }
    }
  }

  /**
   * Generate unique request number
   * Format: PR-YYYYMMDD-XXXX
   */
  private function generateRequestNumber(): string
  {
    $date = now()->format('Ymd');
    $prefix = "PR-{$date}-";

    // Get last request number for today
    $lastRequest = ApOrderPurchaseRequests::where('request_number', 'LIKE', "{$prefix}%")
      ->orderBy('request_number', 'desc')
      ->first();

    if ($lastRequest) {
      // Extract sequence number and increment
      $lastSequence = (int)substr($lastRequest->request_number, -4);
      $newSequence = $lastSequence + 1;
    } else {
      $newSequence = 1;
    }

    return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
  }
}
