<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemExpenseResource;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use Illuminate\Http\Request;

class PerDiemExpenseService extends BaseService
{
    public function index(Request $request)
    {
        return $this->getFilteredResults(
            PerDiemExpense::with(['perDiemRequest', 'expenseType']),
            $request,
            PerDiemExpense::filters ?? ['per_diem_request_id' => '=', 'expense_type_id' => '=', 'validated' => '='],
            PerDiemExpense::sorts ?? ['expense_date', 'receipt_amount'],
            PerDiemExpenseResource::class,
        );
    }

    public function show(int $id): ?PerDiemExpense
    {
        return PerDiemExpense::with(['perDiemRequest', 'expenseType'])->find($id);
    }

    public function store(array $data): PerDiemExpense
    {
        return PerDiemExpense::create($data);
    }

    public function update(int $id, array $data): PerDiemExpense
    {
        $expense = PerDiemExpense::findOrFail($id);
        $expense->update($data);
        return $expense->fresh(['perDiemRequest', 'expenseType']);
    }

    public function destroy(int $id): bool
    {
        $expense = PerDiemExpense::findOrFail($id);

        if ($expense->validated) {
            throw new \Exception('No se puede eliminar un gasto que ya fue validado.');
        }

        return $expense->delete();
    }

    public function validate(int $id): PerDiemExpense
    {
        $expense = PerDiemExpense::findOrFail($id);

        if ($expense->validated) {
            throw new \Exception('El gasto ya fue validado.');
        }

        $expense->update([
            'validated' => true,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        return $expense->fresh(['perDiemRequest', 'expenseType']);
    }
}
