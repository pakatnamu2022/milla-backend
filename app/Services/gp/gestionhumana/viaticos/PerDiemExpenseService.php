<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class PerDiemExpenseService
{
    /**
     * Create new expense for a request
     */
    public function create(int $requestId, array $data): PerDiemExpense
    {
        $request = PerDiemRequest::findOrFail($requestId);

        // Validate that request allows expenses
        if (!in_array($request->status, ['in_progress', 'pending_settlement', 'settled'])) {
            throw new Exception('Cannot add expenses. Request must be in progress, pending settlement, or settled.');
        }

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
            'receipt_path' => $data['receipt_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Update request total spent
        $this->updateRequestTotalSpent($requestId);

        return $expense->fresh(['expenseType', 'request']);
    }

    /**
     * Update expense
     */
    public function update(int $expenseId, array $data): PerDiemExpense
    {
        $expense = PerDiemExpense::findOrFail($expenseId);

        // Validate that expense can be updated
        if ($expense->validated) {
            throw new Exception('Cannot update expense. Expense has already been validated.');
        }

        $expense->update([
            'expense_type_id' => $data['expense_type_id'] ?? $expense->expense_type_id,
            'expense_date' => $data['expense_date'] ?? $expense->expense_date,
            'concept' => $data['concept'] ?? $expense->concept,
            'receipt_amount' => $data['receipt_amount'] ?? $expense->receipt_amount,
            'company_amount' => $data['company_amount'] ?? $expense->company_amount,
            'employee_amount' => $data['employee_amount'] ?? $expense->employee_amount,
            'receipt_type' => $data['receipt_type'] ?? $expense->receipt_type,
            'receipt_number' => $data['receipt_number'] ?? $expense->receipt_number,
            'receipt_path' => $data['receipt_path'] ?? $expense->receipt_path,
            'notes' => $data['notes'] ?? $expense->notes,
        ]);

        // Update request total spent
        $this->updateRequestTotalSpent($expense->per_diem_request_id);

        return $expense->fresh(['expenseType', 'request']);
    }

    /**
     * Delete expense
     */
    public function delete(int $expenseId): bool
    {
        $expense = PerDiemExpense::findOrFail($expenseId);

        // Validate that expense can be deleted
        if ($expense->validated) {
            throw new Exception('Cannot delete expense. Expense has already been validated.');
        }

        $requestId = $expense->per_diem_request_id;
        $deleted = $expense->delete();

        if ($deleted) {
            // Update request total spent
            $this->updateRequestTotalSpent($requestId);
        }

        return $deleted;
    }

    /**
     * Validate expense
     */
    public function validate(int $expenseId, int $validatorId): PerDiemExpense
    {
        $expense = PerDiemExpense::findOrFail($expenseId);

        if ($expense->validated) {
            throw new Exception('Expense has already been validated.');
        }

        $expense->update([
            'validated' => true,
            'validated_by' => $validatorId,
            'validated_at' => now(),
        ]);

        return $expense->fresh(['expenseType', 'validator']);
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

        $totalSpent = PerDiemExpense::where('per_diem_request_id', $requestId)
            ->sum('company_amount');

        $balanceToReturn = max(0, $request->total_budget - $totalSpent);

        $request->update([
            'total_spent' => $totalSpent,
            'balance_to_return' => $balanceToReturn,
        ]);
    }
}
