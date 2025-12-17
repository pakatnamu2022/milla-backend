<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\ExpenseTypeResource;
use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use Illuminate\Http\Request;

class ExpenseTypeService extends BaseService
{
    public function index(Request $request)
    {
        return $this->getFilteredResults(
            ExpenseType::with('parent', 'children'),
            $request,
            ExpenseType::filters,
            ExpenseType::sorts,
            ExpenseTypeResource::class,
        );
    }

    public function active(Request $request)
    {
        return $this->getFilteredResults(
            ExpenseType::active()->with('parent'),
            $request,
            ExpenseType::filters,
            ExpenseType::sorts,
            ExpenseTypeResource::class,
        );
    }

    public function parents(Request $request)
    {
        return $this->getFilteredResults(
            ExpenseType::parents(),
            $request,
            ExpenseType::filters,
            ExpenseType::sorts,
            ExpenseTypeResource::class,
        );
    }

    public function store(array $data): ExpenseType
    {
        return ExpenseType::create($data);
    }

    public function show(int $id): ?ExpenseType
    {
        return ExpenseType::with('parent', 'children')->find($id);
    }

    public function update(int $id, array $data): ExpenseType
    {
        $expenseType = ExpenseType::findOrFail($id);
        $expenseType->update($data);
        return $expenseType->fresh(['parent', 'children']);
    }

    public function destroy(int $id): bool
    {
        $expenseType = ExpenseType::findOrFail($id);

        if ($expenseType->hasChildren()) {
            throw new \Exception('No se puede eliminar el tipo de gasto porque tiene subtipos asociados.');
        }

        if ($expenseType->perDiemRates()->exists()) {
            throw new \Exception('No se puede eliminar el tipo de gasto porque tiene tarifas asociadas.');
        }

        return $expenseType->delete();
    }
}
