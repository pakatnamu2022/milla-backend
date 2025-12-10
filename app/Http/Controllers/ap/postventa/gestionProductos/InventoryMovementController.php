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
  protected $inventoryMovementService;
  protected InventoryMovementService $service;

  public function __construct(InventoryMovementService $service)
  {
    $this->inventoryMovementService = new InventoryMovementService();
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
      $movement = $this->inventoryMovementService->createAdjustment(
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
      return $this->inventoryMovementService->reverseStockFromMovement($id);
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
      $result = $this->inventoryMovementService->createTransfer(
        $request->only([
          // Transfer data
          'document_type',
          'warehouse_origin_id',
          'warehouse_destination_id',
          'document_series_id',
          'movement_date',
          'notes',
          'reason_in_out_id',
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
      $result = $this->inventoryMovementService->updateTransfer(
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
      $this->inventoryMovementService->destroyTransfer($id);

      return $this->success([
        'message' => 'Transferencia eliminada correctamente. El stock ha sido revertido.'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Send shipping guide to Nubefact/SUNAT
   * After this, the transfer cannot be edited
   *
   * @param int $id Movement ID
   * @return JsonResponse
   */
  public function sendShippingGuideToNubefact(int $id): JsonResponse
  {
    try {
      $movement = $this->inventoryMovementService->find($id);

      // Validate movement has shipping guide (using polymorphic relation)
      if (!$movement->reference_id || $movement->reference_type !== 'App\\Models\\ap\\comercial\\ShippingGuides') {
        return $this->error('Este movimiento no tiene una guía de remisión asociada', 400);
      }

      $shippingGuide = $movement->reference;

      // Validate guide is not already sent
      if ($shippingGuide->is_sunat_registered) {
        return $this->error('La guía de remisión ya fue enviada a SUNAT', 400);
      }

      // TODO: Call Nubefact service to send the guide
      // For now, just mark as sent (implement Nubefact integration later)
      $shippingGuide->markAsSent();

      return $this->success([
        'message' => 'Guía de remisión enviada a SUNAT exitosamente. La transferencia ya no puede ser editada.',
        'shipping_guide' => $shippingGuide->fresh(),
        'can_edit' => false,
        'can_receive' => true, // Now can be received at destination
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
      $movements = $this->inventoryMovementService->getProductMovementHistory(
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

      $movements = $this->inventoryMovementService->getKardex($request);

      // Return with pagination preserved
      // Format: { data: [], links: {}, meta: {} }
      return $movements;
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
