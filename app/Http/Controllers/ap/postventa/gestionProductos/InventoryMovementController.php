<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\gestionProductos\IndexInventoryMovementRequest;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryMovementController extends Controller
{
  protected $inventoryMovementService;
  protected InventoryMovementService $service;

  public function __construct(InventoryMovementService $service)
  {
    $this->inventoryMovementService = new InventoryMovementService();
    $this->service = $service;
  }

  /**
   * Display a listing of purchase receptions
   */
  public function index(IndexInventoryMovementRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Show a specific inventory movement
   *
   * @param int $id
   * @return JsonResponse
   */
  public function show(int $id): JsonResponse
  {
    try {
      $movement = InventoryMovement::with([
        'warehouse',
        'warehouseDestination',
        'user',
        'details.product',
        'reference'
      ])->findOrFail($id);

      return response()->json($movement);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Movimiento de inventario no encontrado',
        'error' => $e->getMessage()
      ], 404);
    }
  }

  /**
   * Create inventory adjustment (loss, damage, theft, etc.)
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function createAdjustment(Request $request): JsonResponse
  {
    // Validate request
    $validator = Validator::make($request->all(), [
      'movement_type' => 'required|in:ADJUSTMENT_IN,ADJUSTMENT_OUT',
      'warehouse_id' => 'required|exists:warehouse,id',
      'reason_in_out_id' => 'nullable|exists:ap_post_venta_masters,id',
      'movement_date' => 'nullable|date',
      'notes' => 'nullable|string|max:1000',
      'details' => 'required|array|min:1',
      'details.*.product_id' => 'required|exists:products,id',
      'details.*.quantity' => 'required|numeric|min:0.01',
      'details.*.unit_cost' => 'nullable|numeric|min:0',
      'details.*.batch_number' => 'nullable|string|max:100',
      'details.*.expiration_date' => 'nullable|date',
      'details.*.notes' => 'nullable|string|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'message' => 'Datos de validación incorrectos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $movement = $this->inventoryMovementService->createAdjustment(
        $request->only(['movement_type', 'warehouse_id', 'movement_date', 'notes']),
        $request->details
      );

      return response()->json([
        'message' => 'Ajuste de inventario creado exitosamente',
        'data' => $movement->load(['warehouse', 'user', 'details.product'])
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al crear ajuste de inventario',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
