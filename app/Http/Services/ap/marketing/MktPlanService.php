<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktPlanResource;
use App\Http\Services\ap\marketing\Concerns\NormalizesUppercaseText;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktActivityLocation;
use App\Models\ap\marketing\MktBudget;
use App\Models\ap\marketing\MktBudgetFunding;
use App\Models\ap\marketing\MktKpi;
use App\Models\ap\marketing\MktPlan;
use App\Models\ap\marketing\MktProposal;
use App\Models\ap\marketing\MktPurchaseOrder;
use App\Models\ap\marketing\MktSupport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktPlanService extends BaseService implements BaseServiceInterface
{
  use NormalizesUppercaseText;

  const UPPERCASE_FIELDS = ['name', 'concept', 'description'];

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktPlan::query()->with(['brand:id,name']),
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
      $data['status'] = MktPlan::STATUS_DRAFT;
      $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
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
    unset($data['status']);
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
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

  /**
   * Transiciones de estado permitidas para un Plan de Marketing.
   * El estado no se edita libremente: se mueve mediante acciones explícitas.
   */
  const ALLOWED_TRANSITIONS = [
    MktPlan::STATUS_DRAFT  => [MktPlan::STATUS_ACTIVE, MktPlan::STATUS_CANCELLED],
    MktPlan::STATUS_ACTIVE => [MktPlan::STATUS_CLOSED, MktPlan::STATUS_CANCELLED],
  ];

  public function changeStatus(int $id, string $targetStatus): MktPlanResource
  {
    $plan = $this->find($id);
    $allowed = self::ALLOWED_TRANSITIONS[$plan->status] ?? [];

    if (!in_array($targetStatus, $allowed, true)) {
      throw new Exception("No se puede pasar el plan de '{$plan->status}' a '{$targetStatus}'.");
    }

    DB::beginTransaction();
    try {
      $plan->update(['status' => $targetStatus]);
      $plan->load(['brand', 'budgets']);
      DB::commit();
      return new MktPlanResource($plan);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Elimina el plan junto con toda su información relacionada:
   * presupuestos (y sus financiamientos), actividades (y sus ubicaciones),
   * propuestas, KPIs, órdenes de compra (ligadas por plan o por actividad)
   * y los sustentos de esas actividades/órdenes.
   */
  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $plan = $this->find($id);

      $budgetIds = MktBudget::where('plan_id', $plan->id)->pluck('id');
      $activityIds = MktActivity::whereIn('budget_id', $budgetIds)->pluck('id');

      $orderIds = MktPurchaseOrder::where('plan_id', $plan->id)
        ->orWhereIn('activity_id', $activityIds)
        ->pluck('id');

      MktSupport::whereIn('purchase_order_id', $orderIds)
        ->orWhereIn('activity_id', $activityIds)
        ->delete();

      MktPurchaseOrder::whereIn('id', $orderIds)->delete();
      MktProposal::whereIn('activity_id', $activityIds)->delete();
      MktKpi::whereIn('activity_id', $activityIds)->delete();
      MktActivityLocation::whereIn('activity_id', $activityIds)->delete();
      MktActivity::whereIn('id', $activityIds)->delete();
      MktBudgetFunding::whereIn('budget_id', $budgetIds)->delete();
      MktBudget::whereIn('id', $budgetIds)->delete();

      $plan->delete();
      DB::commit();
      return ['message' => 'Plan de marketing eliminado correctamente, junto con toda la información relacionada'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
