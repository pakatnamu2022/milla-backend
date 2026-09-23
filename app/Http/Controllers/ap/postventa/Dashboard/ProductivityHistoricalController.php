<?php

namespace App\Http\Controllers\ap\postventa\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ap\maestroGeneral\ProductivityMonthlySnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductivityHistoricalController extends Controller
{
    /**
     * Get annual trends for a specific year
     * Returns monthly snapshots for trend analysis
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnnualTrends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'sede_id' => 'nullable|integer|exists:config_sede,id',
        ]);

        $year = $validated['year'];
        $sedeId = $validated['sede_id'] ?? null;

        // If no sede_id, get consolidated data (sum all sedes with workshop)
        if ($sedeId === null) {
            $snapshots = ProductivityMonthlySnapshot::forYear($year)
                ->onlyWithWorkshop()
                ->orderByPeriod('asc')
                ->get()
                ->groupBy(function ($snapshot) {
                    return $snapshot->year . '-' . $snapshot->month;
                });

            if ($snapshots->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos disponibles para el año especificado',
                    'data' => null
                ], 404);
            }

            // Aggregate by month (sum all sedes per month)
            $consolidatedSnapshots = $snapshots->map(function ($monthSnapshots) {
                return $this->aggregateSnapshots($monthSnapshots);
            })->sortBy('month')->values();

            // Build trend data
            $trendData = $this->buildTrendData($consolidatedSnapshots);

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'sede_id' => null,
                    'sede_name' => 'Consolidado (Todas las sedes con taller)',
                    'period_count' => $consolidatedSnapshots->count(),
                    'trends' => $trendData,
                    'summary' => $this->calculateYearSummary($consolidatedSnapshots),
                ],
                'meta' => [
                    'chart_recommendations' => [
                        [
                            'id' => 'productivity_trend',
                            'type' => 'line',
                            'title' => 'Tendencia de Productividad Mensual',
                            'description' => 'Gráfica de líneas mostrando la evolución del % de productividad a lo largo del año',
                            'data_key' => 'trends.productivity_percentage',
                            'color' => '#3b82f6'
                        ],
                        [
                            'id' => 'hours_comparison',
                            'type' => 'bar_grouped',
                            'title' => 'Horas Facturadas vs Estándar',
                            'description' => 'Barras agrupadas comparando horas facturadas y estándar por mes',
                            'data_keys' => ['trends.billed_hours', 'trends.standard_hours'],
                            'colors' => ['#10b981', '#6b7280']
                        ],
                        [
                            'id' => 'ot_performance',
                            'type' => 'line_dual_axis',
                            'title' => 'OTs Cerradas y Cobertura de Mano de Obra',
                            'description' => 'Línea dual mostrando cantidad de OTs cerradas (eje izq) y % de cobertura (eje der)',
                            'data_keys' => ['trends.ots_closed', 'trends.labour_coverage_rate'],
                            'colors' => ['#8b5cf6', '#f59e0b']
                        ],
                        [
                            'id' => 'technician_status',
                            'type' => 'bar_stacked',
                            'title' => 'Distribución de Técnicos por Performance',
                            'description' => 'Barras apiladas mostrando cantidad de técnicos en cada categoría de performance',
                            'data_keys' => [
                                'trends.technicians_exceeded',
                                'trends.technicians_on_track',
                                'trends.technicians_warning',
                                'trends.technicians_critical'
                            ],
                            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                            'labels' => ['Excelente (>100%)', 'En Meta (99-100%)', 'Alerta (70-99%)', 'Crítico (<70%)']
                        ]
                    ]
                ]
            ]);
        }

        // Get specific sede snapshots
        $snapshots = ProductivityMonthlySnapshot::forYear($year)
            ->where('sede_id', $sedeId)
            ->orderByPeriod('asc')
            ->get();

        if ($snapshots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay datos disponibles para el año especificado',
                'data' => null
            ], 404);
        }

        // Build trend data
        $trendData = $this->buildTrendData($snapshots);

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'sede_id' => $sedeId,
                'sede_name' => $snapshots->first()->sede?->suc_abrev ?? "Sede {$sedeId}",
                'period_count' => $snapshots->count(),
                'trends' => $trendData,
                'summary' => $this->calculateYearSummary($snapshots),
            ],
            'meta' => [
                'chart_recommendations' => [
                    [
                        'id' => 'productivity_trend',
                        'type' => 'line',
                        'title' => 'Tendencia de Productividad Mensual',
                        'description' => 'Gráfica de líneas mostrando la evolución del % de productividad a lo largo del año',
                        'data_key' => 'trends.productivity_percentage',
                        'color' => '#3b82f6'
                    ],
                    [
                        'id' => 'hours_comparison',
                        'type' => 'bar_grouped',
                        'title' => 'Horas Facturadas vs Estándar',
                        'description' => 'Barras agrupadas comparando horas facturadas y estándar por mes',
                        'data_keys' => ['trends.billed_hours', 'trends.standard_hours'],
                        'colors' => ['#10b981', '#6b7280']
                    ],
                    [
                        'id' => 'ot_performance',
                        'type' => 'line_dual_axis',
                        'title' => 'OTs Cerradas y Cobertura de Mano de Obra',
                        'description' => 'Línea dual mostrando cantidad de OTs cerradas (eje izq) y % de cobertura (eje der)',
                        'data_keys' => ['trends.ots_closed', 'trends.labour_coverage_rate'],
                        'colors' => ['#8b5cf6', '#f59e0b']
                    ],
                    [
                        'id' => 'technician_status',
                        'type' => 'bar_stacked',
                        'title' => 'Distribución de Técnicos por Performance',
                        'description' => 'Barras apiladas mostrando cantidad de técnicos en cada categoría de performance',
                        'data_keys' => [
                            'trends.technicians_exceeded',
                            'trends.technicians_on_track',
                            'trends.technicians_warning',
                            'trends.technicians_critical'
                        ],
                        'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                        'labels' => ['Excelente (>100%)', 'En Meta (99-100%)', 'Alerta (70-99%)', 'Crítico (<70%)']
                    ]
                ]
            ]
        ]);
    }

    /**
     * Compare multiple years
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function compareYears(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'years' => 'required|array|min:2|max:5',
            'years.*' => 'required|integer|min:2020|max:2100',
            'sede_id' => 'nullable|integer|exists:config_sede,id',
        ]);

        $years = $validated['years'];
        $sedeId = $validated['sede_id'] ?? null;

        $comparisonData = [];

        // If no sede_id, get consolidated data for each year
        if ($sedeId === null) {
            foreach ($years as $year) {
                $snapshots = ProductivityMonthlySnapshot::forYear($year)
                    ->onlyWithWorkshop()
                    ->orderByPeriod('asc')
                    ->get()
                    ->groupBy(function ($snapshot) {
                        return $snapshot->year . '-' . $snapshot->month;
                    });

                // Aggregate by month
                $consolidatedSnapshots = $snapshots->map(function ($monthSnapshots) {
                    return $this->aggregateSnapshots($monthSnapshots);
                })->sortBy('month')->values();

                $comparisonData[$year] = [
                    'year' => $year,
                    'months_with_data' => $consolidatedSnapshots->count(),
                    'trends' => $this->buildTrendData($consolidatedSnapshots),
                    'summary' => $this->calculateYearSummary($consolidatedSnapshots),
                ];
            }
        } else {
            // Get snapshots for specific sede
            $snapshots = ProductivityMonthlySnapshot::whereIn('year', $years)
                ->where('sede_id', $sedeId)
                ->orderByPeriod('asc')
                ->get()
                ->groupBy('year');

            foreach ($years as $year) {
                $yearSnapshots = $snapshots->get($year, collect());

                $comparisonData[$year] = [
                    'year' => $year,
                    'months_with_data' => $yearSnapshots->count(),
                    'trends' => $this->buildTrendData($yearSnapshots),
                    'summary' => $this->calculateYearSummary($yearSnapshots),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'years' => $years,
                'sede_id' => $sedeId,
                'sede_name' => $sedeId ? null : 'Consolidado (Todas las sedes con taller)',
                'comparison' => $comparisonData,
            ],
            'meta' => [
                'chart_recommendations' => [
                    [
                        'id' => 'multi_year_productivity',
                        'type' => 'line_multi',
                        'title' => 'Comparación Multi-Anual de Productividad',
                        'description' => 'Líneas múltiples comparando % de productividad entre años',
                        'data_source' => 'comparison[year].trends.productivity_percentage',
                        'legend' => true
                    ],
                    [
                        'id' => 'year_summary_bars',
                        'type' => 'bar_grouped',
                        'title' => 'Resumen Anual Comparativo',
                        'description' => 'Barras agrupadas comparando totales anuales entre años',
                        'metrics' => ['total_billed_hours', 'total_earnings', 'avg_productivity_percentage']
                    ]
                ]
            ]
        ]);
    }

    /**
     * Get specific month snapshot
     *
     * @param int $year
     * @param int $month
     * @param Request $request
     * @return JsonResponse
     */
    public function getMonthSnapshot(int $year, int $month, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => 'nullable|integer|exists:config_sede,id',
        ]);

        $sedeId = $validated['sede_id'] ?? null;

        // If no sede_id, get consolidated snapshot (aggregate all sedes with workshop)
        if ($sedeId === null) {
            $snapshots = ProductivityMonthlySnapshot::forYear($year)
                ->where('month', $month)
                ->onlyWithWorkshop()
                ->get();

            if ($snapshots->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay snapshot disponible para el período especificado',
                    'data' => null
                ], 404);
            }

            // Aggregate all sedes for this month
            $snapshot = $this->aggregateSnapshots($snapshots);

            // Get previous month for comparison
            $previousMonth = Carbon::create($year, $month, 1)->subMonth();
            $previousSnapshots = ProductivityMonthlySnapshot::forYear($previousMonth->year)
                ->where('month', $previousMonth->month)
                ->onlyWithWorkshop()
                ->get();

            $previousSnapshot = $previousSnapshots->isNotEmpty()
                ? $this->aggregateSnapshots($previousSnapshots)
                : null;

            $comparison = $previousSnapshot ? $this->calculateMonthComparison($snapshot, $previousSnapshot) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'snapshot' => $snapshot,
                    'period_description' => Carbon::create($year, $month, 1)->locale('es')->isoFormat('MMMM YYYY'),
                    'sede_name' => 'Consolidado (Todas las sedes con taller)',
                    'comparison_with_previous_month' => $comparison,
                ],
                'meta' => [
                    'chart_recommendations' => [
                        [
                            'id' => 'kpi_cards',
                            'type' => 'kpi_cards',
                            'title' => 'KPIs Principales',
                            'description' => 'Tarjetas mostrando métricas clave con comparación vs mes anterior',
                            'metrics' => [
                                [
                                    'key' => 'average_productivity_percentage',
                                    'label' => 'Productividad Promedio',
                                    'format' => 'percentage',
                                    'trend' => 'comparison_with_previous_month.productivity_percentage_change'
                                ],
                                [
                                    'key' => 'total_earnings',
                                    'label' => 'Ganancias Totales',
                                    'format' => 'currency',
                                    'trend' => 'comparison_with_previous_month.earnings_change'
                                ],
                                [
                                    'key' => 'total_ots_closed',
                                    'label' => 'OTs Cerradas',
                                    'format' => 'number',
                                    'trend' => 'comparison_with_previous_month.ots_closed_change'
                                ],
                                [
                                    'key' => 'labour_coverage_rate',
                                    'label' => 'Cobertura de M.O.',
                                    'format' => 'percentage',
                                    'trend' => 'comparison_with_previous_month.labour_coverage_change'
                                ]
                            ]
                        ],
                        [
                            'id' => 'status_donut',
                            'type' => 'donut',
                            'title' => 'Distribución de Técnicos por Performance',
                            'description' => 'Gráfica de dona mostrando proporción de técnicos en cada categoría',
                            'data_keys' => [
                                'snapshot.technicians_exceeded',
                                'snapshot.technicians_on_track',
                                'snapshot.technicians_warning',
                                'snapshot.technicians_critical'
                            ],
                            'labels' => ['Excelente', 'En Meta', 'Alerta', 'Crítico'],
                            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
                        ]
                    ]
                ]
            ]);
        }

        // Get specific sede snapshot
        $snapshot = ProductivityMonthlySnapshot::where('year', $year)
            ->where('month', $month)
            ->where('sede_id', $sedeId)
            ->first();

        if (!$snapshot) {
            return response()->json([
                'success' => false,
                'message' => 'No hay snapshot disponible para el período especificado',
                'data' => null
            ], 404);
        }

        // Get previous month for comparison
        $previousMonth = Carbon::create($year, $month, 1)->subMonth();
        $previousSnapshot = ProductivityMonthlySnapshot::where('year', $previousMonth->year)
            ->where('month', $previousMonth->month)
            ->where('sede_id', $sedeId)
            ->first();

        $comparison = $previousSnapshot ? $this->calculateMonthComparison($snapshot, $previousSnapshot) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'snapshot' => $snapshot,
                'period_description' => $snapshot->period_description,
                'comparison_with_previous_month' => $comparison,
            ],
            'meta' => [
                'chart_recommendations' => [
                    [
                        'id' => 'kpi_cards',
                        'type' => 'kpi_cards',
                        'title' => 'KPIs Principales',
                        'description' => 'Tarjetas mostrando métricas clave con comparación vs mes anterior',
                        'metrics' => [
                            [
                                'key' => 'average_productivity_percentage',
                                'label' => 'Productividad Promedio',
                                'format' => 'percentage',
                                'trend' => 'comparison_with_previous_month.productivity_percentage_change'
                            ],
                            [
                                'key' => 'total_earnings',
                                'label' => 'Ganancias Totales',
                                'format' => 'currency',
                                'trend' => 'comparison_with_previous_month.earnings_change'
                            ],
                            [
                                'key' => 'total_ots_closed',
                                'label' => 'OTs Cerradas',
                                'format' => 'number',
                                'trend' => 'comparison_with_previous_month.ots_closed_change'
                            ],
                            [
                                'key' => 'labour_coverage_rate',
                                'label' => 'Cobertura de M.O.',
                                'format' => 'percentage',
                                'trend' => 'comparison_with_previous_month.labour_coverage_change'
                            ]
                        ]
                    ],
                    [
                        'id' => 'status_donut',
                        'type' => 'donut',
                        'title' => 'Distribución de Técnicos por Performance',
                        'description' => 'Gráfica de dona mostrando proporción de técnicos en cada categoría',
                        'data_keys' => [
                            'snapshot.technicians_exceeded',
                            'snapshot.technicians_on_track',
                            'snapshot.technicians_warning',
                            'snapshot.technicians_critical'
                        ],
                        'labels' => ['Excelente', 'En Meta', 'Alerta', 'Crítico'],
                        'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
                    ]
                ]
            ]
        ]);
    }

    /**
     * Get multi-year summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMultiYearSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_year' => 'nullable|integer|min:2020',
            'end_year' => 'nullable|integer|max:2100',
            'sede_id' => 'nullable|integer|exists:config_sede,id',
        ]);

        $startYear = $validated['start_year'] ?? (int) Carbon::now()->subYears(2)->year;
        $endYear = $validated['end_year'] ?? (int) Carbon::now()->year;
        $sedeId = $validated['sede_id'] ?? null;

        $snapshots = ProductivityMonthlySnapshot::whereBetween('year', [$startYear, $endYear])
            ->forSede($sedeId)
            ->orderByPeriod('asc')
            ->get();

        $yearlyData = $snapshots->groupBy('year')->map(function ($yearSnapshots, $year) {
            return [
                'year' => $year,
                'months_count' => $yearSnapshots->count(),
                'summary' => $this->calculateYearSummary($yearSnapshots),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'start_year' => $startYear,
                'end_year' => $endYear,
                'sede_id' => $sedeId,
                'total_snapshots' => $snapshots->count(),
                'yearly_data' => $yearlyData,
            ],
            'meta' => [
                'chart_recommendations' => [
                    [
                        'id' => 'long_term_trend',
                        'type' => 'line',
                        'title' => 'Tendencia de Productividad Multi-Anual',
                        'description' => 'Vista consolidada de productividad a través de los años',
                        'data_source' => 'yearly_data[].summary.avg_productivity_percentage',
                        'smooth' => true
                    ]
                ]
            ]
        ]);
    }

    /**
     * Build trend data from snapshots collection
     */
    private function buildTrendData($snapshots): array
    {
        $months = [];
        $productivity = [];
        $billedHours = [];
        $standardHours = [];
        $earnings = [];
        $otsClosed = [];
        $labourCoverage = [];
        $exceeded = [];
        $onTrack = [];
        $warning = [];
        $critical = [];

        foreach ($snapshots as $snapshot) {
            $monthNames = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
            ];

            $months[] = $monthNames[$snapshot->month];
            $productivity[] = (float) $snapshot->average_productivity_percentage;
            $billedHours[] = (float) $snapshot->total_billed_hours;
            $standardHours[] = (float) $snapshot->total_standard_hours;
            $earnings[] = (float) $snapshot->total_earnings;
            $otsClosed[] = $snapshot->total_ots_closed;
            $labourCoverage[] = (float) $snapshot->labour_coverage_rate;
            $exceeded[] = $snapshot->technicians_exceeded;
            $onTrack[] = $snapshot->technicians_on_track;
            $warning[] = $snapshot->technicians_warning;
            $critical[] = $snapshot->technicians_critical;
        }

        return [
            'labels' => $months,
            'productivity_percentage' => $productivity,
            'billed_hours' => $billedHours,
            'standard_hours' => $standardHours,
            'earnings' => $earnings,
            'ots_closed' => $otsClosed,
            'labour_coverage_rate' => $labourCoverage,
            'technicians_exceeded' => $exceeded,
            'technicians_on_track' => $onTrack,
            'technicians_warning' => $warning,
            'technicians_critical' => $critical,
        ];
    }

    /**
     * Calculate year summary
     */
    private function calculateYearSummary($snapshots): array
    {
        if ($snapshots->isEmpty()) {
            return [
                'total_billed_hours' => 0,
                'total_standard_hours' => 0,
                'total_earnings' => 0,
                'total_ots_closed' => 0,
                'avg_productivity_percentage' => 0,
                'avg_labour_coverage_rate' => 0,
            ];
        }

        return [
            'total_billed_hours' => round($snapshots->sum('total_billed_hours'), 2),
            'total_standard_hours' => round($snapshots->sum('total_standard_hours'), 2),
            'total_earnings' => round($snapshots->sum('total_earnings'), 2),
            'total_ots_closed' => $snapshots->sum('total_ots_closed'),
            'avg_productivity_percentage' => round($snapshots->avg('average_productivity_percentage'), 2),
            'avg_labour_coverage_rate' => round($snapshots->avg('labour_coverage_rate'), 2),
            'avg_technicians' => round($snapshots->avg('total_technicians'), 0),
            'best_month' => [
                'month' => $snapshots->sortByDesc('average_productivity_percentage')->first()->month,
                'productivity' => $snapshots->max('average_productivity_percentage'),
            ],
            'worst_month' => [
                'month' => $snapshots->sortBy('average_productivity_percentage')->first()->month,
                'productivity' => $snapshots->min('average_productivity_percentage'),
            ],
        ];
    }

    /**
     * Calculate month-to-month comparison
     */
    private function calculateMonthComparison($current, $previous): array
    {
        return [
            'productivity_percentage_change' => round($current->average_productivity_percentage - $previous->average_productivity_percentage, 2),
            'billed_hours_change' => round($current->total_billed_hours - $previous->total_billed_hours, 2),
            'earnings_change' => round($current->total_earnings - $previous->total_earnings, 2),
            'ots_closed_change' => $current->total_ots_closed - $previous->total_ots_closed,
            'labour_coverage_change' => round($current->labour_coverage_rate - $previous->labour_coverage_rate, 2),
            'technicians_change' => $current->total_technicians - $previous->total_technicians,
        ];
    }

    /**
     * Aggregate multiple snapshots (from different sedes) into a single consolidated snapshot
     * Sums all numeric values and calculates weighted averages for percentages
     */
    private function aggregateSnapshots($snapshots): object
    {
        $totalBilledHours = $snapshots->sum('total_billed_hours');
        $totalStandardHours = $snapshots->sum('total_standard_hours');
        $totalOtsClosed = $snapshots->sum('total_ots_closed');
        $otsWithLabour = $snapshots->sum('ots_with_labour_charged');

        // Calculate weighted average productivity percentage
        // Weight each sede's productivity by their standard hours
        $weightedProductivity = 0;
        if ($totalStandardHours > 0) {
            foreach ($snapshots as $snapshot) {
                $weight = $snapshot->total_standard_hours / $totalStandardHours;
                $weightedProductivity += $snapshot->average_productivity_percentage * $weight;
            }
        }

        // Calculate labour coverage rate from aggregated data
        $labourCoverageRate = $totalOtsClosed > 0
            ? round(($otsWithLabour / $totalOtsClosed) * 100, 2)
            : 0;

        // Calculate avg hours per OT from aggregated data
        $avgHoursPerOt = $totalOtsClosed > 0
            ? round($totalBilledHours / $totalOtsClosed, 2)
            : 0;

        // Calculate billing rate from aggregated data
        $billingRate = $totalStandardHours > 0
            ? round(($totalBilledHours / $totalStandardHours) * 100, 2)
            : 0;

        return (object) [
            'year' => $snapshots->first()->year,
            'month' => $snapshots->first()->month,
            'sede_id' => null, // Consolidated
            'total_technicians' => $snapshots->sum('total_technicians'),
            'total_billed_hours' => round($totalBilledHours, 2),
            'total_standard_hours' => round($totalStandardHours, 2),
            'total_productivity_hours' => round($snapshots->sum('total_productivity_hours'), 2),
            'total_earnings' => round($snapshots->sum('total_earnings'), 2),
            'average_productivity_percentage' => round($weightedProductivity, 2),
            'total_ots_closed' => $totalOtsClosed,
            'ots_with_labour_charged' => $otsWithLabour,
            'ots_without_labour_charged' => $snapshots->sum('ots_without_labour_charged'),
            'avg_hours_per_ot' => $avgHoursPerOt,
            'billing_rate' => $billingRate,
            'reentry_rate' => round($snapshots->avg('reentry_rate'), 2),
            'labour_coverage_rate' => $labourCoverageRate,
            'technicians_exceeded' => $snapshots->sum('technicians_exceeded'),
            'technicians_on_track' => $snapshots->sum('technicians_on_track'),
            'technicians_warning' => $snapshots->sum('technicians_warning'),
            'technicians_critical' => $snapshots->sum('technicians_critical'),
        ];
    }
}