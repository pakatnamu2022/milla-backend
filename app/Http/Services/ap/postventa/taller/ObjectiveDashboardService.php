<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ObjectiveSedePeriodPv;
use App\Models\ap\postventa\taller\ConceptObjectivePeriodPv;
use App\Models\ap\postventa\taller\ObjectiveAdvisorsPeriodPv;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ObjectiveDashboardService
{
  /**
   * Get consolidated dashboard data for a specific period
   *
   * @param int $year
   * @param int $month
   * @param int|null $sedeId If null, returns all headquarters
   * @param bool $useCache
   * @return array
   */
  public function getDashboardData(int $year, int $month, ?int $sedeId = null, bool $useCache = true): array
  {
    $cacheKey = "objective_dashboard_{$year}_{$month}_" . ($sedeId ?? 'all');

    if (!$useCache) {
      Cache::forget($cacheKey);
    }

    return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($year, $month, $sedeId) {
      // Get period info
      $period = $this->getPeriodInfo($year, $month);

      // Get all objectives for this period
      $objectivesQuery = ObjectiveSedePeriodPv::with(['sede', 'conceptObjectives.area', 'conceptObjectives.typePlannings'])
        ->where('year', $year)
        ->where('month', $month);

      if ($sedeId) {
        $objectivesQuery->where('sede_id', $sedeId);
      }

      $objectives = $objectivesQuery->get();

      if ($objectives->isEmpty()) {
        return [
          'period' => $period,
          'executive_summary' => $this->getEmptyExecutiveSummary(),
          'headquarters_comparison' => ['ranking' => [], 'chart_data' => []],
          'headquarters_detail' => []
        ];
      }

      // Calculate progress for each headquarter
      $headquartersDetail = [];
      foreach ($objectives as $objective) {
        $headquartersDetail[] = $this->calculateHeadquarterDetail($objective, $year, $month);
      }

      // Calculate executive summary
      $executiveSummary = $this->calculateExecutiveSummary($headquartersDetail, $period);

      // Create ranking and comparison data
      $headquartersComparison = $this->createHeadquartersComparison($headquartersDetail);

      return [
        'period' => $period,
        'executive_summary' => $executiveSummary,
        'headquarters_comparison' => $headquartersComparison,
        'headquarters_detail' => $headquartersDetail
      ];
    });
  }

  /**
   * Get period information
   */
  private function getPeriodInfo(int $year, int $month): array
  {
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();
    $currentDate = Carbon::now();

    $daysInMonth = $startDate->daysInMonth;
    $daysElapsed = $currentDate->greaterThan($endDate)
      ? $daysInMonth
      : ($currentDate->lessThan($startDate) ? 0 : $currentDate->day);

    return [
      'year' => $year,
      'month' => $month,
      'name' => $startDate->translatedFormat('F Y'),
      'start_date' => $startDate->format('Y-m-d'),
      'end_date' => $endDate->format('Y-m-d'),
      'current_date' => $currentDate->format('Y-m-d'),
      'days_in_month' => $daysInMonth,
      'days_elapsed' => $daysElapsed,
      'days_remaining' => max(0, $daysInMonth - $daysElapsed)
    ];
  }

  /**
   * Calculate complete detail for a single headquarter
   */
  private function calculateHeadquarterDetail(ObjectiveSedePeriodPv $objective, int $year, int $month): array
  {
    $sedeId = $objective->sede_id;
    $totalObjective = (float)$objective->amount;

    // Separate concepts by area
    $workshopConcepts = $objective->conceptObjectives->where('area_id', ApMasters::AREA_TALLER);
    $counterConcepts = $objective->conceptObjectives->where('area_id', ApMasters::AREA_MESON);
    $vehicleCrossingConcepts = $objective->conceptObjectives->where('is_vehicular_crossing', true);

    // Calculate workshop (TALLER)
    $workshopData = $this->calculateWorkshopProgress($workshopConcepts, $sedeId, $year, $month);

    // Calculate counter (MESON)
    $counterData = $this->calculateCounterProgress($counterConcepts, $sedeId, $year, $month);

    // Calculate vehicle crossing (PASO VEHICULAR)
    $vehicleCrossingData = $this->calculateVehicleCrossingProgress($vehicleCrossingConcepts, $sedeId, $year, $month);

    // Calculate total progress
    $totalProgress = $workshopData['progress'] + $counterData['progress'];
    $completionPercentage = $totalObjective > 0 ? round(($totalProgress / $totalObjective) * 100, 2) : 0;

    return [
      'id' => $sedeId,
      'name' => $objective->sede->description ?? '',
      'abbreviation' => $objective->sede->abreviatura ?? '',
      'total_objective' => $totalObjective,
      'total_progress' => round($totalProgress, 2),
      'completion_percentage' => $completionPercentage,
      'status' => $this->getStatus($completionPercentage),
      'workshop' => $workshopData,
      'counter' => $counterData,
      'vehicle_crossing' => $vehicleCrossingData
    ];
  }

  /**
   * Calculate workshop (TALLER) progress
   */
  private function calculateWorkshopProgress($concepts, int $sedeId, int $year, int $month): array
  {
    $totalObjective = $concepts->sum('sub_amount');

    if ($totalObjective == 0) {
      return [
        'objective' => 0,
        'progress' => 0,
        'completion_percentage' => 0,
        'status' => 'not_applicable',
        'by_concept' => [],
        'by_brand' => [],
        'top_advisors' => []
      ];
    }

    // Get all type_planning_ids from concepts
    $typePlanningIds = $concepts->flatMap(function ($concept) {
      return $concept->typePlannings->pluck('id');
    })->unique()->toArray();

    // Calculate billing from work orders
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

    // Get electronic documents related to work orders with the specified type plannings
    $documents = ElectronicDocument::query()
      ->whereBetween('fecha_de_emision', [$startDate, $endDate])
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->where(function ($q) use ($sedeId, $typePlanningIds) {
        // SIMPLE invoicing: work_order_id direct
        $q->whereHas('workOrder', function ($subQ) use ($sedeId, $typePlanningIds) {
          $subQ->where('sede_id', $sedeId)
            ->whereHas('items', function ($itemQ) use ($typePlanningIds) {
              $itemQ->whereIn('type_planning_id', $typePlanningIds);
            });
        })
          // MASSIVE invoicing: internal notes
          ->orWhereHas('internalNotes.workOrder', function ($subQ) use ($sedeId, $typePlanningIds) {
            $subQ->where('sede_id', $sedeId)
              ->whereHas('items', function ($itemQ) use ($typePlanningIds) {
                $itemQ->whereIn('type_planning_id', $typePlanningIds);
              });
          });
      })
      ->with([
        'workOrder.vehicle.model.family.brand',
        'workOrder.items',
        'workOrder.advisor',
        'internalNotes.workOrder.vehicle.model.family.brand',
        'internalNotes.workOrder.items',
        'internalNotes.workOrder.advisor'
      ])
      ->get();

    // Calculate totals
    $totalBilling = 0;
    $brandBreakdown = [];
    $conceptBreakdown = [];
    $advisorBreakdown = [];

    foreach ($documents as $document) {
      $workOrders = collect();

      // SIMPLE: direct work order
      if ($document->workOrder) {
        $workOrders->push($document->workOrder);
      }

      // MASSIVE: work orders from internal notes
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        $workOrders = $workOrders->merge(
          $document->internalNotes->pluck('workOrder')->filter()
        );
      }

      foreach ($workOrders as $workOrder) {
        // Check if work order has items with matching type_planning_id
        $matchingItems = $workOrder->items->whereIn('type_planning_id', $typePlanningIds);
        if ($matchingItems->isEmpty()) {
          continue;
        }

        // Calculate amount (considering credit notes)
        $multiplier = $document->sunat_concept_document_type_id === SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA ? -1 : 1;
        $amount = (float)$document->total * $multiplier;

        $totalBilling += $amount;

        // By brand - check if brand is marketed
        $brand = $workOrder->vehicle?->model?->family?->brand;
        $brandName = 'OTRAS MARCAS';

        if ($brand) {
          // If is_marketed = 1, use brand name; otherwise group as "OTRAS MARCAS"
          $brandName = $brand->is_marketed ? $brand->name : 'OTRAS MARCAS';
        }

        if (!isset($brandBreakdown[$brandName])) {
          $brandBreakdown[$brandName] = [
            'brand_name' => $brandName,
            'total_billing' => 0,
            'vehicle_count' => 0
          ];
        }
        $brandBreakdown[$brandName]['total_billing'] += $amount;
        $brandBreakdown[$brandName]['vehicle_count']++;

        // By advisor (if applicable)
        if ($workOrder->advisor) {
          $advisorId = $workOrder->advisor->id;
          $advisorName = $workOrder->advisor->nombre_completo;

          if (!isset($advisorBreakdown[$advisorId])) {
            // Get advisor objective if exists
            $advisorObjective = ObjectiveAdvisorsPeriodPv::whereHas('conceptObjectivePeriod', function ($q) use ($concepts) {
              $q->whereIn('id', $concepts->pluck('id'));
            })
              ->where('worker_id', $advisorId)
              ->first();

            $advisorBreakdown[$advisorId] = [
              'advisor_id' => $advisorId,
              'advisor_name' => $advisorName,
              'objective' => $advisorObjective ? (float)$advisorObjective->amount : 0,
              'progress' => 0
            ];
          }
          $advisorBreakdown[$advisorId]['progress'] += $amount;
        }
      }
    }

    // Calculate percentages for brands
    foreach ($brandBreakdown as &$brand) {
      $brand['percentage_of_total'] = $totalBilling > 0
        ? round(($brand['total_billing'] / $totalBilling) * 100, 2)
        : 0;
      $brand['total_billing'] = round($brand['total_billing'], 2);
    }

    // Calculate advisor completion and rank
    $advisorBreakdown = collect($advisorBreakdown)->map(function ($advisor) {
      $advisor['progress'] = round($advisor['progress'], 2);
      $advisor['completion_percentage'] = $advisor['objective'] > 0
        ? round(($advisor['progress'] / $advisor['objective']) * 100, 2)
        : 0;
      $advisor['status'] = $this->getStatus($advisor['completion_percentage']);
      return $advisor;
    })->sortByDesc('completion_percentage')->values();

    // Add ranking
    $rank = 1;
    $advisorBreakdown = $advisorBreakdown->map(function ($advisor) use (&$rank) {
      $advisor['rank'] = $rank++;
      return $advisor;
    });

    // Calculate by concept (Internas, P&P, Accesorios, etc.)
    // This would require more detailed logic based on concept types
    // For now, we'll leave it as empty array or implement if needed

    $completionPercentage = $totalObjective > 0 ? round(($totalBilling / $totalObjective) * 100, 2) : 0;

    return [
      'objective' => (float)$totalObjective,
      'progress' => round($totalBilling, 2),
      'completion_percentage' => $completionPercentage,
      'status' => $this->getStatus($completionPercentage),
      'by_concept' => [], // Can be implemented later if needed
      'by_brand' => array_values($brandBreakdown),
      'top_advisors' => $advisorBreakdown->take(10)->toArray()
    ];
  }

  /**
   * Calculate counter (MESON) progress
   */
  private function calculateCounterProgress($concepts, int $sedeId, int $year, int $month): array
  {
    $totalObjective = $concepts->sum('sub_amount');

    if ($totalObjective == 0) {
      return [
        'objective' => 0,
        'progress' => 0,
        'completion_percentage' => 0,
        'status' => 'not_applicable'
      ];
    }

    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

    // Get electronic documents from ApOrderQuotations
    $totalBilling = ElectronicDocument::query()
      ->whereBetween('fecha_de_emision', [$startDate, $endDate])
      ->where('anulado', false)
      ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
      ->whereHas('orderQuotation', function ($q) use ($sedeId) {
        $q->where('sede_id', $sedeId)
          ->where('area_id', ApMasters::AREA_MESON);
      })
      ->sum(DB::raw('CASE
        WHEN sunat_concept_document_type_id = ' . SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA . ' THEN -total
        ELSE total
      END'));

    $completionPercentage = $totalObjective > 0 ? round(($totalBilling / $totalObjective) * 100, 2) : 0;

    return [
      'objective' => (float)$totalObjective,
      'progress' => round($totalBilling, 2),
      'completion_percentage' => $completionPercentage,
      'status' => $this->getStatus($completionPercentage)
    ];
  }

  /**
   * Calculate vehicle crossing (PASO VEHICULAR) progress
   */
  private function calculateVehicleCrossingProgress($concepts, int $sedeId, int $year, int $month): array
  {
    // Get the objective (it's a count, not an amount)
    $concept = $concepts->first();

    if (!$concept) {
      return [
        'objective' => 0,
        'progress' => 0,
        'completion_percentage' => 0,
        'status' => 'not_applicable',
        'by_brand' => []
      ];
    }

    $totalObjective = (float)$concept->sub_amount;

    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

    // Count work orders with vehicle_inspection_id (reception) in this period
    $workOrdersQuery = ApWorkOrder::query()
      ->where('sede_id', $sedeId)
      ->whereNotNull('vehicle_inspection_id')
      ->whereBetween('opening_date', [$startDate, $endDate])
      ->with('vehicle.model.family.brand');

    $workOrders = $workOrdersQuery->get();
    $totalCount = $workOrders->count();

    // Group by brand
    $brandBreakdown = [];
    foreach ($workOrders as $workOrder) {
      // Check if brand is marketed
      $brand = $workOrder->vehicle?->model?->family?->brand;
      $brandName = 'OTRAS MARCAS';

      if ($brand) {
        // If is_marketed = 1, use brand name; otherwise group as "OTRAS MARCAS"
        $brandName = $brand->is_marketed ? $brand->name : 'OTRAS MARCAS';
      }

      if (!isset($brandBreakdown[$brandName])) {
        $brandBreakdown[$brandName] = [
          'brand_name' => $brandName,
          'count' => 0
        ];
      }
      $brandBreakdown[$brandName]['count']++;
    }

    // Calculate percentages
    foreach ($brandBreakdown as &$brand) {
      $brand['percentage_of_total'] = $totalCount > 0
        ? round(($brand['count'] / $totalCount) * 100, 2)
        : 0;
    }

    $completionPercentage = $totalObjective > 0 ? round(($totalCount / $totalObjective) * 100, 2) : 0;

    return [
      'objective' => (int)$totalObjective,
      'progress' => $totalCount,
      'completion_percentage' => $completionPercentage,
      'status' => $this->getStatus($completionPercentage),
      'by_brand' => array_values($brandBreakdown)
    ];
  }

  /**
   * Calculate executive summary from headquarters detail
   */
  private function calculateExecutiveSummary(array $headquartersDetail, array $period): array
  {
    $totalObjective = array_sum(array_column($headquartersDetail, 'total_objective'));
    $totalProgress = array_sum(array_column($headquartersDetail, 'total_progress'));
    $completionPercentage = $totalObjective > 0 ? round(($totalProgress / $totalObjective) * 100, 2) : 0;

    // Calculate expected percentage based on days elapsed
    $expectedPercentage = $period['days_in_month'] > 0
      ? round(($period['days_elapsed'] / $period['days_in_month']) * 100, 2)
      : 0;

    $difference = $completionPercentage - $expectedPercentage;

    // Determine trend (would need historical data, for now simplified)
    $trend = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'stable');

    return [
      'total_objective' => round($totalObjective, 2),
      'total_progress' => round($totalProgress, 2),
      'completion_percentage' => $completionPercentage,
      'status' => $this->getStatus($completionPercentage),
      'trend' => $trend,
      'days_remaining' => $period['days_remaining'],
      'expected_vs_real' => [
        'expected_percentage' => $expectedPercentage,
        'real_percentage' => $completionPercentage,
        'difference' => round($difference, 2)
      ]
    ];
  }

  /**
   * Create headquarters comparison and ranking
   */
  private function createHeadquartersComparison(array $headquartersDetail): array
  {
    // Sort by completion percentage DESC
    usort($headquartersDetail, function ($a, $b) {
      return $b['completion_percentage'] <=> $a['completion_percentage'];
    });

    // Create ranking
    $ranking = [];
    $rank = 1;
    foreach ($headquartersDetail as $hq) {
      $ranking[] = [
        'id' => $hq['id'],
        'name' => $hq['name'],
        'abbreviation' => $hq['abbreviation'],
        'total_objective' => $hq['total_objective'],
        'total_progress' => $hq['total_progress'],
        'completion_percentage' => $hq['completion_percentage'],
        'status' => $hq['status'],
        'rank' => $rank++,
        'areas_summary' => [
          'workshop' => [
            'objective' => $hq['workshop']['objective'],
            'progress' => $hq['workshop']['progress'],
            'completion_percentage' => $hq['workshop']['completion_percentage'],
            'status' => $hq['workshop']['status']
          ],
          'counter' => [
            'objective' => $hq['counter']['objective'],
            'progress' => $hq['counter']['progress'],
            'completion_percentage' => $hq['counter']['completion_percentage'],
            'status' => $hq['counter']['status']
          ],
          'vehicle_crossing' => [
            'objective' => $hq['vehicle_crossing']['objective'],
            'progress' => $hq['vehicle_crossing']['progress'],
            'completion_percentage' => $hq['vehicle_crossing']['completion_percentage'],
            'status' => $hq['vehicle_crossing']['status']
          ]
        ]
      ];
    }

    // Create chart data
    $chartData = [
      'labels' => array_column($ranking, 'abbreviation'),
      'datasets' => [
        'objectives' => array_column($ranking, 'total_objective'),
        'progress' => array_column($ranking, 'total_progress'),
        'completion_percentages' => array_column($ranking, 'completion_percentage')
      ]
    ];

    return [
      'ranking' => $ranking,
      'chart_data' => $chartData
    ];
  }

  /**
   * Get status based on completion percentage
   */
  private function getStatus(float $percentage): string
  {
    if ($percentage < 70) {
      return 'critical';
    } elseif ($percentage < 85) {
      return 'warning';
    } elseif ($percentage <= 100) {
      return 'on_track';
    } else {
      return 'exceeded';
    }
  }

  /**
   * Get empty executive summary
   */
  private function getEmptyExecutiveSummary(): array
  {
    return [
      'total_objective' => 0,
      'total_progress' => 0,
      'completion_percentage' => 0,
      'status' => 'not_applicable',
      'trend' => 'stable',
      'days_remaining' => 0,
      'expected_vs_real' => [
        'expected_percentage' => 0,
        'real_percentage' => 0,
        'difference' => 0
      ]
    ];
  }
}
