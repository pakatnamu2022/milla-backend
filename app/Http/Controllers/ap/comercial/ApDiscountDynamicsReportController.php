<?php

namespace App\Http\Controllers\ap\comercial;

use App\Exports\ap\comercial\DiscountDynamicsReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\DiscountDynamicsReportRequest;
use App\Http\Services\ap\comercial\ApDiscountDynamicsReportService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApDiscountDynamicsReportController extends Controller
{
  protected ApDiscountDynamicsReportService $service;

  public function __construct(ApDiscountDynamicsReportService $service)
  {
    $this->service = $service;
  }

  public function index(DiscountDynamicsReportRequest $request): JsonResponse
  {
    $data = $this->service->generate(
      $request->input('fecha_inicio'),
      $request->input('fecha_fin'),
      $request->input('sede_id')
    );

    return response()->json([
      'data'  => $data->values(),
      'total' => $data->count(),
    ]);
  }

  public function export(DiscountDynamicsReportRequest $request): BinaryFileResponse
  {
    try {
      $fechaInicio = $request->input('fecha_inicio');
      $fechaFin    = $request->input('fecha_fin');

      $data = $this->service->generate(
        $fechaInicio,
        $fechaFin,
        $request->input('sede_id')
      );

      $suffix = ($fechaInicio && $fechaFin)
        ? str_replace('-', '_', $fechaInicio) . '_a_' . str_replace('-', '_', $fechaFin)
        : now()->format('Y_m_d');

      $filename = 'Reporte_Descuentos_Dynamics_' . $suffix . '.xlsx';

      return Excel::download(new DiscountDynamicsReportExport($data), $filename);
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
