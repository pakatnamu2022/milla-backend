<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemExpenseResource;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionsistema\DigitalFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class PerDiemExpenseService extends BaseService
{
  protected DigitalFileService $digitalFileService;

  // Configuración de rutas para archivos
  private const FILE_PATHS = [
    'receipt_file' => '/gh/viaticos/gastos/',
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  /**
   * Get expenses by request
   */
  public function getByRequest(int $requestId, Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      PerDiemExpense::where('per_diem_request_id', $requestId),
      $request,
      [],
      [],
      PerDiemExpenseResource::class);
  }

  /**
   * Create new expense for a request
   */
  public function create(int $requestId, array $data): PerDiemExpense
  {
    try {
      DB::beginTransaction();

      $request = PerDiemRequest::findOrFail($requestId);

      // Validate that request allows expenses
      if (!in_array($request->status, ['in_progress', 'pending_settlement', 'settled'])) {
        throw new Exception('No se pueden agregar gastos a una solicitud en el estado actual. Asegúrese de que la solicitud esté en progreso o en liquidación.');
      }
      
      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

      $expense = PerDiemExpense::create([
        'per_diem_request_id' => $requestId,
        'expense_type_id' => $data['expense_type_id'],
        'expense_date' => $data['expense_date'],
        'concept' => $data['concept'],
        'receipt_amount' => $data['receipt_amount'],
        'company_amount' => $data['company_amount'],
        'employee_amount' => $data['employee_amount'],
        'receipt_type' => $data['receipt_type'],
        'receipt_number' => $data['receipt_number'] ?? null,
        'notes' => $data['notes'] ?? null,
      ]);

      // Subir archivo y actualizar URL
      if (!empty($files)) {
        $this->uploadAndAttachFiles($expense, $files);
      }

      // Update request total spent
      $this->updateRequestTotalSpent($requestId);

      DB::commit();
      return $expense->fresh(['expenseType', 'request']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update expense
   */
  public function update(int $expenseId, array $data): PerDiemExpense
  {
    try {
      DB::beginTransaction();

      $expense = PerDiemExpense::findOrFail($expenseId);

      // Validate that expense can be updated
      if ($expense->validated) {
        throw new Exception('Cannot update expense. Expense has already been validated.');
      }

      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

      $expense->update([
        'expense_type_id' => $data['expense_type_id'] ?? $expense->expense_type_id,
        'expense_date' => $data['expense_date'] ?? $expense->expense_date,
        'concept' => $data['concept'] ?? $expense->concept,
        'receipt_amount' => $data['receipt_amount'] ?? $expense->receipt_amount,
        'company_amount' => $data['company_amount'] ?? $expense->company_amount,
        'employee_amount' => $data['employee_amount'] ?? $expense->employee_amount,
        'receipt_type' => $data['receipt_type'] ?? $expense->receipt_type,
        'receipt_number' => $data['receipt_number'] ?? $expense->receipt_number,
        'notes' => $data['notes'] ?? $expense->notes,
      ]);

      // Si hay nuevo archivo, subirlo y actualizar URL
      if (!empty($files)) {
        // Eliminar archivo anterior si existe
        $this->deleteAttachedFiles($expense);

        // Subir nuevo archivo
        $this->uploadAndAttachFiles($expense, $files);
      }

      // Update request total spent
      $this->updateRequestTotalSpent($expense->per_diem_request_id);

      DB::commit();
      return $expense->fresh(['expenseType', 'request']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete expense
   */
  public function delete(int $expenseId): bool
  {
    try {
      DB::beginTransaction();

      $expense = PerDiemExpense::findOrFail($expenseId);

      // Validate that expense can be deleted
      if ($expense->validated) {
        throw new Exception('Cannot delete expense. Expense has already been validated.');
      }

      $requestId = $expense->per_diem_request_id;

      // Eliminar archivos asociados si existen
      $this->deleteAttachedFiles($expense);

      $deleted = $expense->delete();

      if ($deleted) {
        // Update request total spent
        $this->updateRequestTotalSpent($requestId);
      }

      DB::commit();
      return $deleted;
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Validate expense
   */
  public function validateExpense(int $expenseId, int $validatorId): PerDiemExpense
  {
    $expense = PerDiemExpense::findOrFail($expenseId);

    if ($expense->validated) {
      throw new Exception('Expense has already been validated.');
    }

    if ($expense->rejected) {
      throw new Exception('Cannot validate a rejected expense.');
    }

    $expense->update([
      'validated' => true,
      'validated_by' => $validatorId,
      'validated_at' => now(),
    ]);

    return $expense->fresh(['expenseType', 'validator']);
  }

  /**
   * Reject expense
   */
  public function rejectExpense(int $expenseId, int $rejectorId, string $rejectionReason): PerDiemExpense
  {
    try {
      DB::beginTransaction();

      $expense = PerDiemExpense::findOrFail($expenseId);

      if ($expense->validated) {
        throw new Exception('Cannot reject a validated expense.');
      }

      if ($expense->rejected) {
        throw new Exception('Expense has already been rejected.');
      }

      $expense->update([
        'rejected' => true,
        'rejected_by' => $rejectorId,
        'rejected_at' => now(),
        'rejection_reason' => $rejectionReason,
      ]);

      // Update request total spent since this expense is now rejected
      $this->updateRequestTotalSpent($expense->per_diem_request_id);

      DB::commit();
      return $expense->fresh(['expenseType', 'rejector']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get total expenses by request
   */
  public function getTotalExpensesByRequest(int $requestId): array
  {
    $expenses = PerDiemExpense::where('per_diem_request_id', $requestId)->get();

    return [
      'count' => $expenses->count(),
      'total_receipt_amount' => $expenses->sum('receipt_amount'),
      'total_company_amount' => $expenses->sum('company_amount'),
      'total_employee_amount' => $expenses->sum('employee_amount'),
      'validated_count' => $expenses->where('validated', true)->count(),
    ];
  }

  /**
   * Get expenses by date for a request
   */
  public function getExpensesByDate(int $requestId, string $date): Collection
  {
    return PerDiemExpense::where('per_diem_request_id', $requestId)
      ->whereDate('expense_date', $date)
      ->with(['expenseType'])
      ->orderBy('expense_date', 'asc')
      ->get();
  }

  /**
   * Update request total spent
   */
  protected function updateRequestTotalSpent(int $requestId): void
  {
    $request = PerDiemRequest::findOrFail($requestId);

    // Only sum expenses that are not rejected
    $totalSpent = PerDiemExpense::where('per_diem_request_id', $requestId)
      ->where('rejected', false)
      ->sum('company_amount');

    $balanceToReturn = max(0, $request->total_budget - $totalSpent);

    $request->update([
      'total_spent' => $totalSpent,
      'balance_to_return' => $balanceToReturn,
    ]);
  }

  /**
   * Extrae los archivos del array de datos
   */
  private function extractFiles(array &$data): array
  {
    $files = [];

    foreach (array_keys(self::FILE_PATHS) as $field) {
      if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
        $files[$field] = $data[$field];
        unset($data[$field]); // Remover del array para no guardarlo en la BD
      }
    }

    return $files;
  }

  /**
   * Sube archivos y actualiza el modelo con las URLs
   */
  private function uploadAndAttachFiles(PerDiemExpense $expense, array $files): void
  {
    foreach ($files as $field => $file) {
      $path = self::FILE_PATHS[$field];
      $model = $expense->getTable();

      // Subir archivo usando DigitalFileService
      $digitalFile = $this->digitalFileService->store($file, $path, 'public', $model);

      // Actualizar el campo del expense con la URL
      $expense->receipt_path = $digitalFile->url;
    }

    $expense->save();
  }

  /**
   * Elimina archivos asociados al modelo
   */
  private function deleteAttachedFiles(PerDiemExpense $expense): void
  {
    if ($expense->receipt_path) {
      // Buscar el archivo digital asociado y eliminarlo
      $digitalFile = DigitalFile::where('url', $expense->receipt_path)->first();

      if ($digitalFile) {
        $this->digitalFileService->destroy($digitalFile->id);
      }
    }
  }

  /**
   * Get remaining budget for a specific expense type on a specific date
   */
  public function getRemainingBudget(int $requestId, int $expenseTypeId, string $date): array
  {
    // Get the per diem request
    $request = PerDiemRequest::findOrFail($requestId);

    // Get the budget for this expense type
    $budget = $request->budgets()
      ->where('expense_type_id', $expenseTypeId)
      ->first();

    if (!$budget) {
      throw new Exception('No se encontró presupuesto para este tipo de gasto en la solicitud.');
    }

    // Calculate total spent for this expense type on this date (excluding rejected expenses)
    $totalSpentOnDate = PerDiemExpense::where('per_diem_request_id', $requestId)
      ->where('expense_type_id', $expenseTypeId)
      ->whereDate('expense_date', $date)
      ->where('rejected', false)
      ->sum('company_amount');

    // Calculate remaining budget for the day
    $remainingBudget = $budget->daily_amount - $totalSpentOnDate;

    return [
      'per_diem_request_id' => $requestId,
      'expense_type_id' => $expenseTypeId,
      'date' => $date,
      'daily_amount' => $budget->daily_amount,
      'total_spent_on_date' => $totalSpentOnDate,
      'remaining_budget' => max(0, $remainingBudget), // Never return negative
      'is_over_budget' => $remainingBudget < 0,
    ];
  }
}
