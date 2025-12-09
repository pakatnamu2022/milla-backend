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

        // Business validation: validate that request allows expenses
        if (!in_array($request->status, ['in_progress', 'pending_settlement', 'settled'])) {
            throw new Exception('No se pueden agregar gastos. La solicitud debe estar en progreso, pendiente de liquidación o liquidada.');
        }

        // Add request id to data
        $data['per_diem_request_id'] = $requestId;

        // Create expense
        $expense = PerDiemExpense::create($data);

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

        // Business validation: only non-validated expenses can be updated
        if ($expense->validated) {
            throw new Exception('No se puede actualizar el gasto. El gasto ya ha sido validado.');
        }

        // Update expense
        $expense->update($data);

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

        // Business validation: only non-validated expenses can be deleted
        if ($expense->validated) {
            throw new Exception('No se puede eliminar el gasto. El gasto ya ha sido validado.');
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
    public function validate(int $expenseId, array $data): PerDiemExpense
    {
        $expense = PerDiemExpense::findOrFail($expenseId);

        // Business validation: expense cannot be validated twice
        if ($expense->validated) {
            throw new Exception('El gasto ya ha sido validado.');
        }

        // Update expense
        $expense->update($data);

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
