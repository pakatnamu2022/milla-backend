<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Services\Dashboard\Comercial\DashboardComercialService;
use Illuminate\Http\Request;

/**
 * Controlador para endpoints de indicadores del Dashboard Comercial
 *
 * Maneja las peticiones HTTP y delega la lógica al servicio correspondiente
 */
class DashboardComercialController extends Controller
{
  protected $dashboardService;

  public function __construct(DashboardComercialService $dashboardService)
  {
    $this->dashboardService = $dashboardService;
  }

  /**
   * Obtiene indicadores totales por rango de fechas
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getTotalsByDateRange(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
    ]);

    $data = $this->dashboardService->getTotalsByDateRange(
      $request->date_from,
      $request->date_to
    );

    return response()->json([
      'success' => true,
      'data' => $data,
      'periodo' => [
        'fecha_inicio' => $request->date_from,
        'fecha_fin' => $request->date_to,
      ],
    ]);
  }

  /**
   * Obtiene indicadores totales agrupados por sede
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getTotalsBySede(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
    ]);

    $data = $this->dashboardService->getTotalsBySede(
      $request->date_from,
      $request->date_to
    );

    return response()->json([
      'success' => true,
      'data' => $data,
      'periodo' => [
        'fecha_inicio' => $request->date_from,
        'fecha_fin' => $request->date_to,
      ],
    ]);
  }

  /**
   * Obtiene indicadores agrupados por sede y marca de vehículo
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getTotalsBySedeAndBrand(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
    ]);

    $data = $this->dashboardService->getTotalsBySedeAndBrand(
      $request->date_from,
      $request->date_to
    );

    return response()->json([
      'success' => true,
      'data' => $data,
      'periodo' => [
        'fecha_inicio' => $request->date_from,
        'fecha_fin' => $request->date_to,
      ],
    ]);
  }

  /**
   * Obtiene indicadores agrupados por asesor
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getTotalsByAdvisor(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
    ]);

    $data = $this->dashboardService->getTotalsByAdvisor(
      $request->date_from,
      $request->date_to
    );

    return response()->json([
      'success' => true,
      'data' => $data,
      'periodo' => [
        'fecha_inicio' => $request->date_from,
        'fecha_fin' => $request->date_to,
      ],
    ]);
  }
}