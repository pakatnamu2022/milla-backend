<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\GetRemainingBudgetRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\RejectPerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemExpenseRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\ValidatePerDiemExpenseRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemExpenseResource;
use App\Http\Services\gp\gestionhumana\viaticos\PerDiemExpenseService;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use Exception;

class PerDiemExpenseController extends Controller
{
  protected PerDiemExpenseService $service;

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
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created expense
   */
  public function store(int $requestId, StorePerDiemExpenseRequest $request)
  {
    try {
      $data = $request->all();
      $file = $request->file('receipt_file');
      if ($file) {
        $data['receipt_file'] = $file;
      }
      return $this->success($this->service->store($requestId, $data, $file));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update the specified expense
   */
  public function update(int $expenseId, UpdatePerDiemExpenseRequest $request)
  {
    try {
      $data = $request->all();
      $file = $request->file('receipt_file');
      if ($file) {
        $data['receipt_file'] = $file;
      }
      return $this->success($this->service->update($expenseId, $data, $file));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Remove the specified expense
   */
  public function destroy(int $expenseId)
  {
    try {
      return $this->success($this->service->delete($expenseId));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Validate an expense
   */
  public function isValid(int $expenseId, ValidatePerDiemExpenseRequest $request)
  {
    try {
      $validatorId = auth()->id();
      $expense = $this->service->validateExpense($expenseId, $validatorId);

      return response()->json([
        'success' => true,
        'data' => new PerDiemExpenseResource($expense),
        'message' => 'Gasto validado exitosamente'
      ], 200);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Reject an expense
   */
  public function reject(int $expenseId, RejectPerDiemExpenseRequest $request)
  {
    try {
      $rejectorId = auth()->user()->partner_id;
      $rejectionReason = $request->input('rejection_reason');

      $expense = $this->service->rejectExpense($expenseId, $rejectorId, $rejectionReason);

      return response()->json([
        'success' => true,
        'data' => new PerDiemExpenseResource($expense),
        'message' => 'Gasto rechazado exitosamente'
      ], 200);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get remaining budget for a specific expense type on a specific date
   */
  public function getRemainingBudget(int $requestId, GetRemainingBudgetRequest $request)
  {
    try {
      $expenseTypeId = $request->input('expense_type_id');
      $date = $request->input('date');

      $result = $this->service->getRemainingBudget($requestId, $expenseTypeId, $date);

      return response()->json([
        'success' => true,
        'data' => $result
      ], 200);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
