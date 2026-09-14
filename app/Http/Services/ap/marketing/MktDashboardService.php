<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Services\BaseService;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktBudget;
use App\Models\ap\marketing\MktKpi;
use App\Models\ap\marketing\MktPlan;
use App\Models\ap\marketing\MktPurchaseOrder;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktDashboardService extends BaseService
{
  /** Tipo de cambio aproximado (PEN por USD) cuando no hay ninguno registrado para la fecha. */
  private const DEFAULT_EXCHANGE_RATE = 3.75;

  /** Cache en memoria de tipos de cambio ya resueltos, por fecha (evita repetir la consulta). */
  private array $rateCache = [];

  /**
   * Dashboard de Marketing: todos los montos se reportan en USD, convirtiendo
   * lo que esté en otra moneda (PEN) con el tipo de cambio vigente a la fecha
   * de cada registro, tomado de la tabla exchange_rate.
   */
  public function summary(Request $request): array
  {
    $year = $request->input('year', now()->year);

    $plans = MktPlan::where('year', $year)
      ->withCount('budgets')
      ->get();

    $budgets = MktBudget::whereHas('plan', fn($q) => $q->where('year', $year))
      ->get(['id', 'status', 'currency_id', 'period_month', 'amount_estimated', 'amount_executed']);

    $budgetTotals = $budgets->groupBy('status')
      ->map(function ($group, $status) use ($year) {
        $estimated = 0.0;
        $executed  = 0.0;
        foreach ($group as $budget) {
          $date = $this->periodDate($year, $budget->period_month);
          $estimated += $this->toUsd((float) $budget->amount_estimated, $budget->currency_id, $date);
          $executed  += $this->toUsd((float) $budget->amount_executed, $budget->currency_id, $date);
        }
        return [
          'status'          => $status,
          'total_estimated' => round($estimated, 2),
          'total_executed'  => round($executed, 2),
        ];
      })
      ->values();

    $activityByStatus = MktActivity::whereHas('budget.plan', fn($q) => $q->where('year', $year))
      ->select('status', DB::raw('COUNT(*) as total'))
      ->groupBy('status')
      ->get();

    $orders = MktPurchaseOrder::whereHas('activity.budget.plan', fn($q) => $q->where('year', $year))
      ->get(['id', 'status', 'currency_id', 'amount', 'issue_date']);

    $orderByStatus = $orders->groupBy('status')
      ->map(function ($group, $status) {
        $totalAmount = 0.0;
        foreach ($group as $order) {
          $date = $order->issue_date ? Carbon::parse($order->issue_date)->toDateString() : null;
          $totalAmount += $this->toUsd((float) $order->amount, $order->currency_id, $date);
        }
        return [
          'status'       => $status,
          'total'        => $group->count(),
          'total_amount' => round($totalAmount, 2),
        ];
      })
      ->values();

    $activityIds = MktActivity::whereHas('budget.plan', fn($q) => $q->where('year', $year))->pluck('id');

    // KPIs del año con la actividad (para el canal) precargada, base para lo demás
    $kpiRows = MktKpi::whereIn('activity_id', $activityIds)
      ->where('period_year', $year)
      ->with('activity:id,name,channel')
      ->get();

    // Totales de KPIs del año: la base para medir si las campañas están dando resultado
    $totalLeads         = 0;
    $totalSales          = 0;
    $totalInvestmentUsd = 0.0;
    foreach ($kpiRows as $kpi) {
      $date = $this->periodDate($kpi->period_year, $kpi->period_month);
      $totalLeads         += (int) $kpi->leads;
      $totalSales          += (int) $kpi->sales;
      $totalInvestmentUsd += $this->toUsd((float) $kpi->investment, $kpi->currency_id, $date);
    }

    $kpiTotals = [
      'total_leads'      => $totalLeads,
      'total_sales'      => $totalSales,
      'total_investment' => round($totalInvestmentUsd, 2),
      'conversion_rate'  => $totalLeads > 0 ? round(($totalSales / $totalLeads) * 100, 2) : 0,
      'cost_per_lead'    => $totalLeads > 0 ? round($totalInvestmentUsd / $totalLeads, 2) : 0,
      'cost_per_sale'    => $totalSales > 0 ? round($totalInvestmentUsd / $totalSales, 2) : 0,
    ];

    // Rendimiento por canal: qué canales generan más leads/ventas y a qué costo,
    // clave para orientar la campaña hacia lo que mejor está funcionando
    $kpisByChannel = $kpiRows->groupBy(fn($kpi) => $kpi->activity?->channel ?: 'Sin canal')
      ->map(function ($rows, $channel) {
        $leads      = 0;
        $sales      = 0;
        $investment = 0.0;
        foreach ($rows as $kpi) {
          $date = $this->periodDate($kpi->period_year, $kpi->period_month);
          $leads      += (int) $kpi->leads;
          $sales      += (int) $kpi->sales;
          $investment += $this->toUsd((float) $kpi->investment, $kpi->currency_id, $date);
        }
        return [
          'channel'          => $channel,
          'total_leads'      => $leads,
          'total_sales'      => $sales,
          'total_investment' => round($investment, 2),
          'conversion_rate'  => $leads > 0 ? round(($sales / $leads) * 100, 2) : 0,
          'cost_per_lead'    => $leads > 0 ? round($investment / $leads, 2) : 0,
        ];
      })
      ->sortByDesc('total_sales')
      ->values();

    // Top actividades/campañas por ventas generadas: para reconocer qué campañas replicar
    $topActivities = $kpiRows->groupBy('activity_id')
      ->map(function ($rows) {
        $activity   = $rows->first()->activity;
        $leads      = 0;
        $sales      = 0;
        $investment = 0.0;
        foreach ($rows as $kpi) {
          $date = $this->periodDate($kpi->period_year, $kpi->period_month);
          $leads      += (int) $kpi->leads;
          $sales      += (int) $kpi->sales;
          $investment += $this->toUsd((float) $kpi->investment, $kpi->currency_id, $date);
        }
        return [
          'id'               => $activity?->id,
          'name'             => $activity?->name,
          'channel'          => $activity?->channel,
          'total_leads'      => $leads,
          'total_sales'      => $sales,
          'total_investment' => round($investment, 2),
          'conversion_rate'  => $leads > 0 ? round(($sales / $leads) * 100, 2) : 0,
          'cost_per_lead'    => $leads > 0 ? round($investment / $leads, 2) : 0,
        ];
      })
      ->filter(fn($row) => $row['total_sales'] > 0)
      ->sortByDesc('total_sales')
      ->take(5)
      ->values();

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
      'kpi_totals'        => $kpiTotals,
      'kpis_by_channel'   => $kpisByChannel,
      'top_activities'    => $topActivities,
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
      ->get(['period_month', 'currency_id', 'amount_estimated', 'amount_executed'])
      ->groupBy('period_month')
      ->map(function ($group, $periodMonth) use ($year) {
        $estimated = 0.0;
        $executed  = 0.0;
        foreach ($group as $budget) {
          $date = $this->periodDate($year, $budget->period_month);
          $estimated += $this->toUsd((float) $budget->amount_estimated, $budget->currency_id, $date);
          $executed  += $this->toUsd((float) $budget->amount_executed, $budget->currency_id, $date);
        }
        return [
          'period_month' => (int) $periodMonth,
          'estimated'    => round($estimated, 2),
          'executed'     => round($executed, 2),
        ];
      })
      ->sortBy('period_month')
      ->values();

    // Monthly KPI aggregation
    $monthlyKpis = MktKpi::whereHas('activity', fn($q) => $q->whereIn('budget_id', $budgetIds))
      ->where('period_year', $year)
      ->get(['period_month', 'period_year', 'currency_id', 'leads', 'sales', 'investment'])
      ->groupBy('period_month')
      ->map(function ($group, $periodMonth) {
        $leads      = 0;
        $sales      = 0;
        $investment = 0.0;
        foreach ($group as $kpi) {
          $date = $this->periodDate($kpi->period_year, $kpi->period_month);
          $leads      += (int) $kpi->leads;
          $sales      += (int) $kpi->sales;
          $investment += $this->toUsd((float) $kpi->investment, $kpi->currency_id, $date);
        }
        return [
          'period_month'     => (int) $periodMonth,
          'total_leads'      => $leads,
          'total_sales'      => $sales,
          'total_investment' => round($investment, 2),
        ];
      })
      ->sortBy('period_month')
      ->values();

    // By brand summary
    $byBrand = MktPlan::with('brand')
      ->where('year', $year)
      ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
      ->get()
      ->map(function ($plan) use ($year) {
        $budgetIds = $plan->budgets()->pluck('id');

        $planBudgets = MktBudget::whereIn('id', $budgetIds)
          ->get(['currency_id', 'period_month', 'amount_estimated', 'amount_executed']);
        $estimated = 0.0;
        $executed  = 0.0;
        foreach ($planBudgets as $budget) {
          $date = $this->periodDate($year, $budget->period_month);
          $estimated += $this->toUsd((float) $budget->amount_estimated, $budget->currency_id, $date);
          $executed  += $this->toUsd((float) $budget->amount_executed, $budget->currency_id, $date);
        }

        $activityIds = MktActivity::whereIn('budget_id', $budgetIds)->pluck('id');
        $activities  = $activityIds->count();

        $planOrders = MktPurchaseOrder::whereIn('activity_id', $activityIds)
          ->get(['currency_id', 'amount', 'issue_date']);
        $ordersTotal = 0.0;
        foreach ($planOrders as $order) {
          $date = $order->issue_date ? Carbon::parse($order->issue_date)->toDateString() : null;
          $ordersTotal += $this->toUsd((float) $order->amount, $order->currency_id, $date);
        }

        return [
          'plan_id'          => $plan->id,
          'plan_name'        => $plan->name,
          'brand_id'         => $plan->brand_id,
          'brand_name'       => $plan->brand?->name,
          'concept'          => $plan->concept,
          'amount_estimated' => round($estimated, 2),
          'amount_executed'  => round($executed, 2),
          'activities_count' => $activities,
          'orders_total'     => round($ordersTotal, 2),
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

  /** Última fecha del período mes/año dado, usada como referencia para buscar el tipo de cambio. */
  private function periodDate(?int $year, ?int $month): string
  {
    $year = $year ?: now()->year;
    if (!$month) {
      return Carbon::create($year, 12, 31)->toDateString();
    }
    return Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
  }

  /** Convierte un monto a USD según su moneda de origen y una fecha de referencia. */
  private function toUsd(float $amount, ?int $currencyId, ?string $date): float
  {
    if (!$amount) {
      return 0.0;
    }
    if (!$currencyId || $currencyId === TypeCurrency::USD_ID) {
      return $amount;
    }
    $rate = $this->exchangeRateToUsd($date);
    return round($amount / $rate, 2);
  }

  /** Tipo de cambio PEN -> USD vigente para la fecha (con cache y valor por defecto). */
  private function exchangeRateToUsd(?string $date): float
  {
    $date = $date ?: now()->toDateString();

    if (isset($this->rateCache[$date])) {
      return $this->rateCache[$date];
    }

    $optimal = ExchangeRate::getOptimalExchangeRate($date, TypeCurrency::PEN_ID, TypeCurrency::USD_ID);
    $rate    = $optimal?->rate ? (float) $optimal->rate : self::DEFAULT_EXCHANGE_RATE;

    return $this->rateCache[$date] = $rate;
  }
}
