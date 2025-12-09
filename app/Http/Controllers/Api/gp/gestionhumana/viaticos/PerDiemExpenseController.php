<?php

namespace App\Http\Controllers\Api\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemExpenseRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemExpenseResource;
use App\Services\gp\gestionhumana\viaticos\PerDiemExpenseService;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PerDiemExpenseController extends Controller
{
    protected PerDiemExpenseService $service;

    public function __construct(PerDiemExpenseService $service)
    {
        $this->service = $service;
    }

    /**
     * Get expenses for a per diem request
     */
    public function index(string $requestId): JsonResponse
    {
        try {
            $expenses = PerDiemExpense::where('per_diem_request_id', $requestId)
                ->with(['expenseType', 'validator'])
                ->orderBy('expense_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => PerDiemExpenseResource::collection($expenses),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener gastos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new expense
     */
    public function store(StorePerDiemExpenseRequest $request, string $requestId): JsonResponse
    {
        try {
            $expense = $this->service->create((int) $requestId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Gasto creado exitosamente',
                'data' => new PerDiemExpenseResource($expense),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear gasto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an expense
     */
    public function update(StorePerDiemExpenseRequest $request, string $expenseId): JsonResponse
    {
        try {
            $expense = $this->service->update((int) $expenseId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Gasto actualizado exitosamente',
                'data' => new PerDiemExpenseResource($expense),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar gasto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an expense
     */
    public function destroy(string $expenseId): JsonResponse
    {
        try {
            $this->service->delete((int) $expenseId);

            return response()->json([
                'success' => true,
                'message' => 'Gasto eliminado exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar gasto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate an expense
     */
    public function validate(Request $request, string $expenseId): JsonResponse
    {
        try {
            $validatorId = $request->input('validator_id') ?? auth()->id();
            $expense = $this->service->validate((int) $expenseId, (int) $validatorId);

            return response()->json([
                'success' => true,
                'message' => 'Gasto validado exitosamente',
                'data' => new PerDiemExpenseResource($expense),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar gasto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
