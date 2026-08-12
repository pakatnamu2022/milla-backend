<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Services\BaseService;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktBudget;
use App\Models\ap\marketing\MktPlan;
use App\Models\ap\marketing\MktPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktDashboardService extends BaseService
{
  public function summary(Request $request): array
  {
    $year = $request->input('year', now()->year);

    $plans = MktPlan::where('year', $year)
      ->withCount('budgets')
      ->get();

    $budgetTotals = MktBudget::whereHas('plan', fn($q) => $q->where('year', $year))
      ->select(
        DB::raw('SUM(amount_estimated) as total_estimated'),
        DB::raw('SUM(amount_executed) as total_executed'),
        'status'
      )
      ->groupBy('status')
      ->get();

    $activityByStatus = MktActivity::whereHas('budget.plan', fn($q) => $q->where('year', $year))
      ->select('status', DB::raw('COUNT(*) as total'))
      ->groupBy('status')
      ->get();

    $orderByStatus = MktPurchaseOrder::whereHas('activity.budget.plan', fn($q) => $q->where('year', $year))
      ->select('status', DB::raw('COUNT(*) as total'), DB::raw('SUM(amount) as total_amount'))
      ->groupBy('status')
      ->get();

    return [
      'year'              => $year,
      'plans_count'       => $plans->count(),
      'plans'             => $plans->map(fn($p) => [
        'id'            => $p->id,
        'name'          => $p->name,
        'brand_id'      => $p->brand_id,
        'status'        => $p->status,
        'budgets_count' => $p->budgets_count,
      ]),
      'budget_totals'     => $budgetTotals,
      'activities_status' => $activityByStatus,
      'orders_status'     => $orderByStatus,
    ];
  }

  public function monthly(Request $request): array
  {
    $year    = $request->input('year', now()->year);
    $brandId = $request->input('brand_id');

    $plansQuery = MktPlan::where('year', $year);
    if ($brandId) {
      $plansQuery->where('brand_id', $brandId);
    }
    $planIds = $plansQuery->pluck('id');

    $budgetIds = MktBudget::whereIn('plan_id', $planIds)->pluck('id');

    // Monthly budget breakdown
    $monthlyBudgets = MktBudget::whereIn('plan_id', $planIds)
      ->whereNotNull('period_month')
      ->select(
        'period_month',
        DB::raw('SUM(amount_estimated) as estimated'),
        DB::raw('SUM(amount_executed) as executed')
      )
      ->groupBy('period_month')
      ->orderBy('period_month')
      ->get();

    // Monthly KPI aggregation
    $monthlyKpis = DB::table('mkt_kpis')
      ->join('mkt_activities', 'mkt_kpis.activity_id', '=', 'mkt_activities.id')
      ->whereIn('mkt_activities.budget_id', $budgetIds)
      ->where('mkt_kpis.period_year', $year)
      ->whereNull('mkt_activities.deleted_at')
      ->select(
        'mkt_kpis.period_month',
        DB::raw('SUM(mkt_kpis.leads) as total_leads'),
        DB::raw('SUM(mkt_kpis.sales) as total_sales'),
        DB::raw('SUM(mkt_kpis.investment) as total_investment')
      )
      ->groupBy('mkt_kpis.period_month')
      ->orderBy('mkt_kpis.period_month')
      ->get();

    // By brand summary
    $byBrand = MktPlan::with('brand')
      ->where('year', $year)
      ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
      ->get()
      ->map(function ($plan) {
        $budgetIds = $plan->budgets()->pluck('id');
        $estimated = MktBudget::whereIn('id', $budgetIds)->sum('amount_estimated');
        $executed  = MktBudget::whereIn('id', $budgetIds)->sum('amount_executed');
        $activities = MktActivity::whereIn('budget_id', $budgetIds)->count();
        $orders     = MktPurchaseOrder::whereIn('activity_id',
          MktActivity::whereIn('budget_id', $budgetIds)->pluck('id')
        )->sum('amount');

        return [
          'plan_id'           => $plan->id,
          'plan_name'         => $plan->name,
          'brand_id'          => $plan->brand_id,
          'brand_name'        => $plan->brand?->name,
          'concept'           => $plan->concept,
          'amount_estimated'  => $estimated,
          'amount_executed'   => $executed,
          'activities_count'  => $activities,
          'orders_total'      => $orders,
        ];
      });

    return [
      'year'            => $year,
      'brand_id'        => $brandId,
      'monthly_budgets' => $monthlyBudgets,
      'monthly_kpis'    => $monthlyKpis,
      'by_brand'        => $byBrand,
    ];
  }
}
