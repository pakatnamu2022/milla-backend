<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryMovementController extends Controller
{
  protected $inventoryMovementService;

  public function __construct()
  {
    $this->inventoryMovementService = new InventoryMovementService();
  }

  /**
   * List inventory movements with filters
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request): JsonResponse
  {
    try {
      $query = InventoryMovement::with(['warehouse', 'user', 'details.product']);

      // Apply filters
      if ($request->has('warehouse_id')) {
        $query->where('warehouse_id', $request->warehouse_id);
      }

      if ($request->has('movement_type')) {
        $query->where('movement_type', $request->movement_type);
      }

      if ($request->has('status')) {
        $query->where('status', $request->status);
      }

      if ($request->has('date_from')) {
        $query->whereDate('movement_date', '>=', $request->date_from);
      }

      if ($request->has('date_to')) {
        $query->whereDate('movement_date', '<=', $request->date_to);
      }

      // Apply sorting
      $sortBy = $request->get('sort_by', 'created_at');
      $sortOrder = $request->get('sort_order', 'desc');
      $query->orderBy($sortBy, $sortOrder);

      // Paginate
      $perPage = $request->get('per_page', 15);
      $movements = $query->paginate($perPage);

      return response()->json($movements);
    } catch (Exception $e) {
      return response()->json([
        'message' => 'Error al obtener movimientos de inventario',
        'error' => $e->getMessage()
      ], 500);
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
      'movement_type' => 'required|in:ADJUSTMENT_IN,ADJUSTMENT_OUT,LOSS,DAMAGE',
      'warehouse_id' => 'required|exists:warehouse,id',
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

  /**
   * Get movement types available
   *
   * @return JsonResponse
   */
  public function getMovementTypes(): JsonResponse
  {
    return response()->json([
      'adjustment_types' => [
        [
          'value' => InventoryMovement::TYPE_LOSS,
          'label' => 'Pérdida',
          'description' => 'Productos perdidos (robo, extravío)'
        ],
        [
          'value' => InventoryMovement::TYPE_DAMAGE,
          'label' => 'Daño',
          'description' => 'Productos dañados o defectuosos'
        ],
        [
          'value' => InventoryMovement::TYPE_ADJUSTMENT_OUT,
          'label' => 'Ajuste Negativo',
          'description' => 'Disminución manual de inventario'
        ],
        [
          'value' => InventoryMovement::TYPE_ADJUSTMENT_IN,
          'label' => 'Ajuste Positivo',
          'description' => 'Aumento manual de inventario (stock encontrado)'
        ],
      ],
      'all_types' => [
        ['value' => InventoryMovement::TYPE_PURCHASE_RECEPTION, 'label' => 'Recepción de Compra'],
        ['value' => InventoryMovement::TYPE_SALE, 'label' => 'Venta'],
        ['value' => InventoryMovement::TYPE_ADJUSTMENT_IN, 'label' => 'Ajuste Positivo'],
        ['value' => InventoryMovement::TYPE_ADJUSTMENT_OUT, 'label' => 'Ajuste Negativo'],
        ['value' => InventoryMovement::TYPE_TRANSFER_OUT, 'label' => 'Transferencia Salida'],
        ['value' => InventoryMovement::TYPE_TRANSFER_IN, 'label' => 'Transferencia Entrada'],
        ['value' => InventoryMovement::TYPE_RETURN_IN, 'label' => 'Devolución Entrada'],
        ['value' => InventoryMovement::TYPE_RETURN_OUT, 'label' => 'Devolución Salida'],
        ['value' => InventoryMovement::TYPE_LOSS, 'label' => 'Pérdida'],
        ['value' => InventoryMovement::TYPE_DAMAGE, 'label' => 'Daño'],
      ]
    ]);
  }
}