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

  public function getTotalsByDateRangeTotal(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsByDateRangeTotal(
      $request->date_from,
      $request->date_to,
      $request->type
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

  public function getTotalsByDateRange(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsByDateRangeGrouped(
      $request->date_from,
      $request->date_to,
      $request->type
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

  public function getTotalsBySede(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsBySede(
      $request->date_from,
      $request->date_to,
      $request->type
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

  public function getTotalsBySedeAndBrand(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsBySedeAndBrand(
      $request->date_from,
      $request->date_to,
      $request->type
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

  public function getTotalsByAdvisor(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsByAdvisor(
      $request->date_from,
      $request->date_to,
      $request->type
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
