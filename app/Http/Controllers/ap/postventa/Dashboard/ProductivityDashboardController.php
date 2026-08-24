<?php

namespace App\Http\Controllers\ap\postventa\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Dashboard\ProductivityDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductivityDashboardController extends Controller
{
  protected ProductivityDashboardService $service;

  public function __construct(ProductivityDashboardService $service)
  {
    $this->service = $service;
  }

  /**
   * Get productivity dashboard data for technicians
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function getDashboard(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'date_range' => 'required|array|size:2',
      'date_range.*' => 'required|date',
      'sede_id' => 'nullable|integer|exists:config_sede,id',
      'use_cache' => 'nullable|boolean'
    ]);

    $startDate = $validated['date_range'][0];
    $endDate = $validated['date_range'][1];
    $sedeId = $validated['sede_id'] ?? null;
    $useCache = $validated['use_cache'] ?? true;

    try {
      $dashboardData = $this->service->getDashboardData($startDate, $endDate, $sedeId, $useCache);

      return response()->json([
        'success' => true,
        'data' => $dashboardData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener datos del dashboard de productividad',
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
      'date_range' => 'required|array|size:2',
      'date_range.*' => 'required|date',
      'sede_id' => 'nullable|integer|exists:config_sede,id'
    ]);

    $startDate = $validated['date_range'][0];
    $endDate = $validated['date_range'][1];
    $sedeId = $validated['sede_id'] ?? null;

    try {
      // Force cache refresh
      $dashboardData = $this->service->getDashboardData($startDate, $endDate, $sedeId, false);

      return response()->json([
        'success' => true,
        'message' => 'Dashboard de productividad actualizado correctamente',
        'data' => $dashboardData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al actualizar el dashboard de productividad',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
