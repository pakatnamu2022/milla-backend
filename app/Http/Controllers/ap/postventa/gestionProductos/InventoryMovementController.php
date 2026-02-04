<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\gestionProductos\IndexInventoryMovementRequest;
use App\Http\Requests\ap\postventa\gestionProductos\StoreAdjustmentInventoryRequest;
use App\Http\Requests\ap\postventa\gestionProductos\StoreTransferInventoryRequest;
use App\Http\Requests\ap\postventa\gestionProductos\UpdateInventoryMovementRequest;
use App\Http\Requests\ap\postventa\gestionProductos\UpdateTransferInventoryRequest;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
  protected InventoryMovementService $service;

  public function __construct(InventoryMovementService $service)
  {
    $this->service = $service;
  }

  public function index(IndexInventoryMovementRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function createAdjustment(StoreAdjustmentInventoryRequest $request)
  {
    $request->validated();
    try {
      $movement = $this->service->createAdjustment(
        $request->only(['movement_type', 'warehouse_id', 'movement_date', 'notes', 'reason_in_out_id']),
        $request->details
      );

      return $this->success($movement->load(['warehouse', 'user', 'details.product']));
    } catch (Exception $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateInventoryMovementRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->updateAdjustment($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->service->reverseStockFromMovement($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create warehouse transfer with shipping guide
   * Creates TRANSFER_OUT movement + Shipping Guide (NOT sent to Nubefact yet)
   *
   * @param StoreTransferInventoryRequest $request
   * @return JsonResponse
   */
  public function createTransfer(StoreTransferInventoryRequest $request): JsonResponse
  {
    $request->validated();
    try {
      $result = $this->service->createTransfer(
        $request->only([
          // Transfer data
          'document_type',
          'warehouse_origin_id',
          'warehouse_destination_id',
          'document_series_id',
          'movement_date',
          'notes',
          'reason_in_out_id',
          'item_type',
          // Shipping guide data
          'driver_name',
          'driver_doc',
          'license',
          'plate',
          'transfer_reason_id',
          'transfer_modality_id',
          'transport_company_id',
          'total_packages',
          'total_weight',
          // Business Partners (will be used to get address and ubigeo data)
          'transmitter_origin_id',
          'receiver_destination_id',
        ]),
        $request->details
      );

      return $this->success([
        'message' => 'Transferencia y guía de remisión creadas exitosamente. La guía aún no ha sido enviada a SUNAT.',
        'movement' => $result['movement'],
        'shipping_guide' => $result['shipping_guide'],
        'can_edit' => true, // Can edit while guide is not sent
        'can_send_to_sunat' => true,
      ]);
    } catch (Exception $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update warehouse transfer
   * Only updates simple fields (NOT products)
   * Only allowed if shipping guide has NOT been sent to SUNAT
   *
   * @param UpdateTransferInventoryRequest $request
   * @param int $id Movement ID
   * @return JsonResponse
   */
  public function updateTransfer(UpdateTransferInventoryRequest $request, int $id): JsonResponse
  {
    $request->validated();
    try {
      $result = $this->service->updateTransfer(
        $request->only([
          'movement_date',
          'notes',
          'driver_name',
          'driver_doc',
          'license',
          'plate',
          'transfer_reason_id',
          'transfer_modality_id',
          'transport_company_id',
          'total_packages',
          'total_weight',
        ]),
        $id
      );

      return $this->success([
        'message' => 'Transferencia actualizada correctamente',
        'movement' => $result['movement'],
        'shipping_guide' => $result['shipping_guide'],
      ]);
    } catch (Exception $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Delete warehouse transfer
   * Only allowed if shipping guide has NOT been sent to SUNAT
   * Reverses stock from in_transit back to available
   *
   * @param int $id Movement ID
   * @return JsonResponse
   */
  public function destroyTransfer(int $id): JsonResponse
  {
    try {
      $this->service->destroyTransfer($id);

      return $this->success([
        'message' => 'Transferencia eliminada correctamente. El stock ha sido revertido.'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get movement history for a specific product in a warehouse
   * Returns all inventory movements for a product
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function getProductMovementHistory(int $productId, int $warehouseId, Request $request)
  {
    try {
      $movements = $this->service->getProductMovementHistory(
        $productId,
        $warehouseId,
        $request
      );

      // Return with pagination preserved
      // Format: { data: [], links: {}, meta: {} }
      return $movements;
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get kardex of all inventory movements
   * Returns all inventory movements with optional warehouse filter
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
   */
  public function getKardex(Request $request)
  {
    try {
      $request->validate([
        'warehouse_id' => 'sometimes|integer|exists:warehouse,id',
        'per_page' => 'sometimes|integer|min:1|max:100',
        'date_from' => 'sometimes|date',
        'date_to' => 'sometimes|date',
        'movement_type' => 'sometimes|string',
        'status' => 'sometimes|string',
        'search' => 'nullable|string',
      ]);

      $movements = $this->service->getKardex($request);

      // Return with pagination preserved
      // Format: { data: [], links: {}, meta: {} }
      return $movements;
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create sale outbound movement from quotation
   * Creates SALE type movement referencing an ApOrderQuotation
   *
   * @param int $quotationId Quotation ID
   * @return JsonResponse
   */
  public function createSaleFromQuotation(int $quotationId, Request $request): JsonResponse
  {
    try {
      $movement = $this->service->createSaleFromQuotation($quotationId, [
        'customer_signature_delivery_url' => $request->input('customer_signature_delivery_url'),
        'delivery_document_number' => $request->input('delivery_document_number'),
      ]);

      return $this->success([
        'message' => 'Movimiento de salida por venta creado exitosamente',
        'movement' => $movement,
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get purchase history for a specific product in a warehouse
   * Returns all purchases with prices to track cost variations
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param Request $request
   * @return JsonResponse
   */
  public function getProductPurchaseHistory(int $productId, int $warehouseId, Request $request): JsonResponse
  {
    try {
      $request->validate([
        'date_from' => 'sometimes|date',
        'date_to' => 'sometimes|date|after_or_equal:date_from',
        'search' => 'sometimes|string',
      ]);

      $history = $this->service->getProductPurchaseHistory(
        $productId,
        $warehouseId,
        $request
      );

      return $this->success($history);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Export movement history for a specific product in a warehouse to Excel
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
   */
  public function exportProductMovementHistory(int $productId, int $warehouseId, Request $request)
  {
    try {
      $request->validate([
        'date_from' => 'sometimes|date',
        'date_to' => 'sometimes|date|after_or_equal:date_from',
        'movement_type' => 'sometimes|string',
        'status' => 'sometimes|string',
      ]);

      return $this->service->exportProductMovementHistory(
        $productId,
        $warehouseId,
        $request
      );
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Export purchase history for a specific product in a warehouse to Excel
   *
   * @param int $productId Product ID
   * @param int $warehouseId Warehouse ID
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
   */
  public function exportProductPurchaseHistory(int $productId, int $warehouseId, Request $request)
  {
    try {
      $request->validate([
        'date_from' => 'sometimes|date',
        'date_to' => 'sometimes|date|after_or_equal:date_from',
      ]);

      return $this->service->exportProductPurchaseHistory(
        $productId,
        $warehouseId,
        $request
      );
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
