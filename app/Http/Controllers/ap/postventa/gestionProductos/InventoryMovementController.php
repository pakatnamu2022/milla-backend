<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\gestionProductos\IndexInventoryMovementRequest;
use App\Http\Requests\ap\postventa\gestionProductos\StoreAdjustmentInventoryRequest;
use App\Http\Requests\ap\postventa\gestionProductos\UpdateInventoryMovementRequest;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
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
}
