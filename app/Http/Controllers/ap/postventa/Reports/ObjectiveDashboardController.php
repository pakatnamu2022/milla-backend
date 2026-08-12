<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\taller\ObjectivesDashboardExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\taller\ObjectiveDashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class ObjectiveDashboardController extends Controller
{
  protected ObjectiveDashboardService $service;

  public function __construct(ObjectiveDashboardService $service)
  {
    $this->service = $service;
  }

  /**
   * Get objectives dashboard data for a specific period
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function getDashboard(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'year' => 'required|integer|min:2020|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'sede_id' => 'nullable|integer|exists:config_sede,id',
      'use_cache' => 'nullable|boolean'
    ]);

    $year = $validated['year'];
    $month = $validated['month'];
    $sedeId = $validated['sede_id'] ?? null;
    $useCache = $validated['use_cache'] ?? true;

    try {
      $dashboardData = $this->service->getDashboardData($year, $month, $sedeId, $useCache);

      return response()->json([
        'success' => true,
        'data' => $dashboardData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener datos del dashboard',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Refresh dashboard data (clear cache and recalculate)
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function refreshDashboard(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'year' => 'required|integer|min:2020|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'sede_id' => 'nullable|integer|exists:config_sede,id'
    ]);

    $year = $validated['year'];
    $month = $validated['month'];
    $sedeId = $validated['sede_id'] ?? null;

    try {
      // Force cache refresh
      $dashboardData = $this->service->getDashboardData($year, $month, $sedeId, false);

      return response()->json([
        'success' => true,
        'message' => 'Dashboard actualizado correctamente',
        'data' => $dashboardData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al actualizar el dashboard',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Export objectives dashboard to Excel
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportExcel(Request $request)
  {
    $validated = $request->validate([
      'year' => 'required|integer|min:2020|max:2100',
      'month' => 'required|integer|min:1|max:12',
      'sede_id' => 'nullable|integer|exists:config_sede,id'
    ]);

    $year = $validated['year'];
    $month = $validated['month'];
    $sedeId = $validated['sede_id'] ?? null;

    try {
      // Get dashboard data without cache to ensure fresh data
      $dashboardData = $this->service->getDashboardData($year, $month, $sedeId, false);

      // Generate filename
      $periodName = $dashboardData['period']['name'];
      $sedeName = $sedeId ? '_sede_' . $sedeId : '_todas_sedes';
      $filename = 'dashboard_objetivos_' . strtolower(str_replace(' ', '_', $periodName)) . $sedeName . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

      // Export to Excel
      return Excel::download(
        new ObjectivesDashboardExport($dashboardData),
        $filename
      );
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al exportar el dashboard',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
