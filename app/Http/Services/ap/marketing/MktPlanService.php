<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktPlanResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\marketing\MktPlan;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktPlanService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktPlan::class,
      $request,
      MktPlan::filters,
      MktPlan::sorts,
      MktPlanResource::class
    );
  }

  public function find(int $id): MktPlan
  {
    $plan = MktPlan::with(['brand', 'budgets'])->find($id);
    if (!$plan) {
      throw new Exception('Plan de marketing no encontrado');
    }
    return $plan;
  }

  public function store(mixed $data): MktPlanResource
  {
    DB::beginTransaction();
    try {
      $plan = MktPlan::create($data);
      $plan->load(['brand', 'budgets']);
      DB::commit();
      return new MktPlanResource($plan);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktPlanResource
  {
    return new MktPlanResource($this->find($id));
  }

  public function update(mixed $data): MktPlanResource
  {
    $plan = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $plan->update($data);
      $plan->load(['brand', 'budgets']);
      DB::commit();
      return new MktPlanResource($plan);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $plan = $this->find($id);
      $plan->delete();
      DB::commit();
      return ['message' => 'Plan de marketing eliminado correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
