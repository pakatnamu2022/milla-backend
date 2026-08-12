<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktBudgetFundingResource;
use App\Http\Resources\ap\marketing\MktBudgetResource;
use App\Http\Services\BaseService;
use App\Models\ap\marketing\MktBudget;
use App\Models\ap\marketing\MktBudgetFunding;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktBudgetService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktBudget::class,
      $request,
      MktBudget::filters,
      MktBudget::sorts,
      MktBudgetResource::class
    );
  }

  private function find(int $id): MktBudget
  {
    $budget = MktBudget::with(['plan', 'currency', 'fundings'])->find($id);
    if (!$budget) {
      throw new Exception('Presupuesto no encontrado');
    }
    return $budget;
  }

  public function store(mixed $data): MktBudgetResource
  {
    DB::beginTransaction();
    try {
      $budget = MktBudget::create($data);
      $budget->load(['plan', 'currency', 'fundings']);
      DB::commit();
      return new MktBudgetResource($budget);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktBudgetResource
  {
    return new MktBudgetResource($this->find($id));
  }

  public function update(mixed $data): MktBudgetResource
  {
    $budget = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $budget->update($data);
      $budget->load(['plan', 'currency', 'fundings']);
      DB::commit();
      return new MktBudgetResource($budget);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id)
  {
    $budget = $this->find($id);
    $budget->delete();
    return response()->json(['message' => 'Presupuesto eliminado correctamente']);
  }

  public function addFunding(int $budgetId, mixed $data): MktBudgetFundingResource
  {
    $budget = $this->find($budgetId);
    DB::beginTransaction();
    try {
      $data['budget_id'] = $budget->id;
      $funding = MktBudgetFunding::create($data);
      $funding->load('currency');
      DB::commit();
      return new MktBudgetFundingResource($funding);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
