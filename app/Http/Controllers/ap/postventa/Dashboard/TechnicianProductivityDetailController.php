<?php

namespace App\Http\Controllers\ap\postventa\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Dashboard\TechnicianProductivityDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianProductivityDetailController extends Controller
{
  protected TechnicianProductivityDetailService $service;

  public function __construct(TechnicianProductivityDetailService $service)
  {
    $this->service = $service;
  }

  /**
   * Get detailed productivity information for a specific technician
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function getDetail(Request $request): JsonResponse
  {
    // Validate parameters
    $validated = $request->validate([
      'worker_id' => 'required|integer|exists:rrhh_persona,id',
      'date_range' => 'required|array|size:2',
      'date_range.*' => 'required|date',
      'sede_id' => 'nullable|integer',
    ]);

    try {
      $data = $this->service->getTechnicianProductivityDetail(
        $validated['worker_id'],
        $validated['date_range'][0],
        $validated['date_range'][1],
        $validated['sede_id'] ?? null
      );

      // Always return success, but include validation warning if needed
      $message = $data['validation']['cuadra']
        ? 'Detalle de productividad obtenido correctamente'
        : $data['validation']['mensaje'];

      return response()->json([
        'success' => true,
        'message' => $message,
        'data' => $data
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al obtener el detalle de productividad: ' . $e->getMessage(),
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
