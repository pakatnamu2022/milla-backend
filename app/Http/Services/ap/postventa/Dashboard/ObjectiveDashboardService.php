<?php

namespace App\Http\Services\ap\postventa\Dashboard;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\ObjectiveAdvisorsPeriodPv;
use App\Models\ap\postventa\taller\ObjectiveSedePeriodPv;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    // Calculate progress for each concept dynamically
    $concepts = [];
    $totalProgress = 0;

    foreach ($objective->conceptObjectives as $conceptObjective) {
      $conceptData = $this->calculateConceptProgress($conceptObjective, $sedeId, $year, $month);
      $concepts[] = $conceptData;

      // Sum progress only for non-vehicular crossing concepts (they're counted, not billed)
      if (!$conceptObjective->is_vehicular_crossing) {
        $totalProgress += $conceptData['progress'];
      }
    }

    $completionPercentage = $totalObjective > 0 ? round(($totalProgress / $totalObjective) * 100, 2) : 0;

    return [
      'id' => $sedeId,
      'name' => $objective->sede->description ?? '',
      'abbreviation' => $objective->sede->abreviatura ?? '',
      'total_objective' => $totalObjective,
      'total_progress' => round($totalProgress, 2),
      'completion_percentage' => $completionPercentage,
      'status' => $this->getStatus($completionPercentage),
      'concepts' => $concepts
    ];
  }

  /**
   * Calculate progress for a single concept dynamically
   */
  private function calculateConceptProgress($conceptObjective, int $sedeId, int $year, int $month): array
  {
    $objective = (float)$conceptObjective->sub_amount;
    $areaName = $conceptObjective->area->description ?? 'N/A';

    // Base structure
    $result = [
      'id' => $conceptObjective->id,
      'description' => $conceptObjective->description,
      'area_id' => $conceptObjective->area_id,
      'area_name' => $areaName,
      'is_vehicular_crossing' => (bool)$conceptObjective->is_vehicular_crossing,
      'objective' => $objective,
      'progress' => 0,
      'completion_percentage' => 0,
      'status' => 'not_applicable'
    ];

    // If no objective, return empty
    if ($objective == 0) {
      return $result;
    }

    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

    // Determine calculation method based on concept configuration
    if ($conceptObjective->is_vehicular_crossing) {
      // PASO VEHICULAR: count work orders with vehicle inspection
      $workOrders = ApWorkOrder::query()
        ->where('sede_id', $sedeId)
        ->whereHas('activeVehicleInspectionPivot')
        ->whereBetween('opening_date', [$startDate, $endDate])
        ->with('vehicle.model.family.brand')
        ->get();

      $totalCount = $workOrders->count();
      $completionPercentage = $objective > 0 ? round(($totalCount / $objective) * 100, 2) : 0;

      // Group by brand
      $brandBreakdown = [];
      foreach ($workOrders as $workOrder) {
        $brand = $workOrder->vehicle?->model?->family?->brand;
        $brandName = 'OTRAS MARCAS';

        if ($brand) {
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

      $result['progress'] = $totalCount;
      $result['completion_percentage'] = $completionPercentage;
      $result['status'] = $this->getStatus($completionPercentage);
      $result['by_brand'] = array_values($brandBreakdown);
    } elseif ($conceptObjective->area_id == ApMasters::AREA_TALLER) {
      // TALLER: calculate billing from work orders with specific type_planning_ids
      $typePlanningIds = $conceptObjective->typePlannings->pluck('id')->toArray();

      if (empty($typePlanningIds)) {
        return $result;
      }

      // Get electronic documents related to work orders with the specified type plannings
      $documents = ElectronicDocument::query()
        ->whereBetween('fecha_de_emision', [$startDate, $endDate])
        ->where('anulado', false)
        ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
        ->where('is_advance_payment', false)
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
          'workOrder.labours',
          'workOrder.parts.product',
          'workOrder.typeCurrency',
          'workOrder.exchangeRate',
          'internalNotes.workOrder.vehicle.model.family.brand',
          'internalNotes.workOrder.items',
          'internalNotes.workOrder.advisor',
          'internalNotes.workOrder.labours',
          'internalNotes.workOrder.parts.product',
          'internalNotes.workOrder.typeCurrency',
          'internalNotes.workOrder.exchangeRate',
          'exchangeRate'
        ])
        ->get();

      // Calculate totals
      $totalBilling = 0;
      $brandBreakdown = [];
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
          // Filter by sede_id
          if ($workOrder->sede_id != $sedeId) {
            continue;
          }

          // Check if work order has items with matching type_planning_id
          $matchingItems = $workOrder->items->whereIn('type_planning_id', $typePlanningIds);
          if ($matchingItems->isEmpty()) {
            continue;
          }

          // Calculate amount
          $multiplier = $document->sunat_concept_document_type_id === SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA ? -1 : 1;
          $amount = $this->calculateWorkOrderAmountInSoles($workOrder, $multiplier, $document);

          $totalBilling += $amount;

          // By brand
          $brand = $workOrder->vehicle?->model?->family?->brand;
          $brandName = 'OTRAS MARCAS';

          if ($brand) {
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

          // By advisor
          if ($workOrder->advisor) {
            $advisorId = $workOrder->advisor->id;
            $advisorName = $workOrder->advisor->nombre_completo;

            if (!isset($advisorBreakdown[$advisorId])) {
              // Get advisor objective if exists
              $advisorObjective = ObjectiveAdvisorsPeriodPv::where('concept_objective_period_pv_id', $conceptObjective->id)
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

      $completionPercentage = $objective > 0 ? round(($totalBilling / $objective) * 100, 2) : 0;

      $result['progress'] = round($totalBilling, 2);
      $result['completion_percentage'] = $completionPercentage;
      $result['status'] = $this->getStatus($completionPercentage);
      $result['by_brand'] = array_values($brandBreakdown);
      $result['top_advisors'] = $advisorBreakdown->take(10)->toArray();
    } elseif ($conceptObjective->area_id == ApMasters::AREA_MESON) {
      // REPUESTOS/MESON: calculate billing from order quotations
      $totalBilling = ElectronicDocument::query()
        ->whereBetween('fecha_de_emision', [$startDate, $endDate])
        ->where('anulado', false)
        ->whereIn('status', [ElectronicDocument::STATUS_SENT, ElectronicDocument::STATUS_ACCEPTED])
        ->whereHas('orderQuotation', function ($q) use ($sedeId) {
          $q->where('sede_id', $sedeId)
            ->where('area_id', ApMasters::AREA_MESON);
        })
        ->sum(DB::raw('CASE
          WHEN sunat_concept_document_type_id = ' . SunatConcepts::ID_NOTA_CREDITO_ELECTRONICA . ' THEN -total_gravada
          ELSE total_gravada
        END'));

      $completionPercentage = $objective > 0 ? round(($totalBilling / $objective) * 100, 2) : 0;

      $result['progress'] = round($totalBilling, 2);
      $result['completion_percentage'] = $completionPercentage;
      $result['status'] = $this->getStatus($completionPercentage);
    }

    return $result;
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
      // Build concepts_summary dynamically - simplified version for comparison
      $conceptsSummary = [];

      foreach ($hq['concepts'] as $concept) {
        $conceptsSummary[] = [
          'id' => $concept['id'],
          'description' => $concept['description'],
          'area_name' => $concept['area_name'],
          'objective' => $concept['objective'],
          'progress' => $concept['progress'],
          'completion_percentage' => $concept['completion_percentage'],
          'status' => $concept['status']
        ];
      }

      $ranking[] = [
        'id' => $hq['id'],
        'name' => $hq['name'],
        'abbreviation' => $hq['abbreviation'],
        'total_objective' => $hq['total_objective'],
        'total_progress' => $hq['total_progress'],
        'completion_percentage' => $hq['completion_percentage'],
        'status' => $hq['status'],
        'rank' => $rank++,
        'concepts_summary' => $conceptsSummary
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
   * Calculate work order amount in soles from work order items (labours + parts)
   * This method calculates the actual work value (not the invoice amount) to match objectives
   *
   * @param ApWorkOrder $workOrder
   * @param float $multiplier
   * @param ElectronicDocument|null $document
   * @return float
   */
  private function calculateWorkOrderAmountInSoles($workOrder, float $multiplier = 1, ?ElectronicDocument $document = null): float
  {
    // Calculate base amounts from work order items (WITHOUT IGV)
    $labourCost = $workOrder->labours->sum('net_amount');
    $partsCost = $workOrder->parts->sum('net_amount');
    $totalAmount = $labourCost + $partsCost;

    // If work order is already in PEN, no conversion needed
    if ($workOrder->currency_id == TypeCurrency::PEN_ID) {
      return $totalAmount * $multiplier;
    }

    // Work order is in USD, convert to PEN
    $exchangeRate = null;

    // Try to get exchange rate from document if available
    if ($document && $document->sunat_concept_currency_id === SunatConcepts::CURRENCY_USD && $document->exchangeRate) {
      $exchangeRate = (float)$document->exchangeRate->rate;
    }

    // If not found, try to get from work order
    if (!$exchangeRate && $workOrder->exchange_rate) {
      $exchangeRate = (float)$workOrder->exchange_rate;
    }

    // If not found, try to get from work order relationship
    if (!$exchangeRate && $workOrder->exchangeRate) {
      $exchangeRate = (float)$workOrder->exchangeRate->rate;
    }

    // Default exchange rate if none found
    if (!$exchangeRate) {
      $exchangeRate = 3.75;
    }

    // Convert to PEN
    return ($totalAmount * $exchangeRate) * $multiplier;
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
