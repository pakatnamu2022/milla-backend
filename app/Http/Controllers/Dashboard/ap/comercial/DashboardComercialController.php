<?php

namespace App\Http\Controllers\Dashboard\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\Dashboard\ap\comercial\DashboardComercialService;
use Illuminate\Http\Request;

/**
 * Controlador para endpoints de indicadores del Dashboard comercial
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

  public function getTotalsByUser(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsByUser(
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

  public function getTotalsByCampaign(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
    ]);

    $data = $this->dashboardService->getTotalsByCampaign(
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

  public function getStatsForSalesManager(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
      'boss_id' => 'nullable|integer|exists:rrhh_persona,id',
    ]);

    $data = $this->dashboardService->getStatsForSalesManager(
      $request->date_from,
      $request->date_to,
      $request->type,
      $request->boss_id
    );

    return response()->json([
      'success' => true,
      'data' => $data,
      'period' => [
        'start_date' => $request->date_from,
        'end_date' => $request->date_to,
      ],
    ]);
  }

  public function getDetailsForSalesManager(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'nullable|in:VISITA,LEADS',
      'boss_id' => 'nullable|integer|exists:rrhh_persona,id',
      'worker_id' => 'nullable|integer|exists:rrhh_persona,id',
    ]);

    return $this->success($this->dashboardService->getDetailsForSalesManager(
      $request->date_from,
      $request->date_to,
      $request->type,
      $request->boss_id,
      $request->per_page ?? 50,
      $request->worker_id
    ));
  }

  public function exportStatsForSalesManager(Request $request)
  {
    $request->validate([
      'date_from' => 'required|date|date_format:Y-m-d',
      'date_to' => 'required|date|date_format:Y-m-d|after_or_equal:date_from',
      'type' => 'required|in:VISITA,LEADS',
      'boss_id' => 'nullable|integer|exists:rrhh_persona,id',
      'worker_id' => 'nullable|integer|exists:rrhh_persona,id',
      'format' => 'nullable|in:excel,pdf',
    ]);

    try {
      return $this->dashboardService->exportStatsForSalesManager($request);
    } catch (\Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
