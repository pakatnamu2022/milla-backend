<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\RequestBudgetResource;
use App\Models\gp\gestionhumana\viaticos\RequestBudget;
use Illuminate\Http\Request;

class RequestBudgetService extends BaseService
{
    public function index(Request $request)
    {
        return $this->getFilteredResults(
            RequestBudget::with(['request', 'expenseType']),
            $request,
            RequestBudget::filters,
            RequestBudget::sorts,
            RequestBudgetResource::class,
        );
    }

    public function byRequest(Request $request, int $requestId)
    {
        return $this->getFilteredResults(
            RequestBudget::where('per_diem_request_id', $requestId)->with('expenseType'),
            $request,
            RequestBudget::filters,
            RequestBudget::sorts,
            RequestBudgetResource::class,
        );
    }

    public function store(array $data): RequestBudget
    {
        return RequestBudget::create($data);
    }

    public function show(int $id): ?RequestBudget
    {
        return RequestBudget::with(['request', 'expenseType'])->find($id);
    }

    public function update(int $id, array $data): RequestBudget
    {
        $budget = RequestBudget::findOrFail($id);
        $budget->update($data);
        return $budget->fresh(['request', 'expenseType']);
    }

    public function destroy(int $id): bool
    {
        $budget = RequestBudget::findOrFail($id);
        return $budget->delete();
    }
}
