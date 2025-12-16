<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\ValidatePerDiemExpenseRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemExpenseResource;
use App\Services\gp\gestionhumana\viaticos\PerDiemExpenseService;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;

class PerDiemExpenseController extends Controller
{
  protected $service;

  public function __construct(PerDiemExpenseService $service)
  {
    $this->service = $service;
  }

  /**
   * Display expenses for a per diem request
   */
  public function index(int $requestId, IndexPerDiemExpenseRequest $request)
  {
    try {
      $expenses = PerDiemExpense::where('per_diem_request_id', $requestId)
        ->with(['expenseType', 'validator'])
        ->orderBy('expense_date', 'desc')
        ->get();

      return response()->json([
        'success' => true,
        'data' => PerDiemExpenseResource::collection($expenses)
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Store a newly created expense
   */
  public function store(int $requestId, StorePerDiemExpenseRequest $request)
  {
    try {
      $data = $request->validated();
      $expense = $this->service->create($requestId, $data);

      return response()->json([
        'success' => true,
        'data' => new PerDiemExpenseResource($expense),
        'message' => 'Gasto creado exitosamente'
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Update the specified expense
   */
  public function update(int $expenseId, UpdatePerDiemExpenseRequest $request)
  {
    try {
      $data = $request->validated();
      $expense = $this->service->update($expenseId, $data);

      return response()->json([
        'success' => true,
        'data' => new PerDiemExpenseResource($expense),
        'message' => 'Gasto actualizado exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Remove the specified expense
   */
  public function destroy(int $expenseId)
  {
    try {
      $this->service->delete($expenseId);

      return response()->json([
        'success' => true,
        'message' => 'Gasto eliminado exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Validate an expense
   */
  public function isValid(int $expenseId, ValidatePerDiemExpenseRequest $request)
  {
    try {
      $data = $request->validated();
      $expense = $this->service->validate($expenseId, $data);

      return response()->json([
        'success' => true,
        'data' => new PerDiemExpenseResource($expense),
        'message' => 'Gasto validado exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }
}
