<?php

namespace App\Http\Controllers\ap\comercial;

use App\Exports\DailyDeliveryReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\DailyDeliveryReportRequest;
use App\Http\Services\ap\comercial\ApDailyDeliveryReportService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApDailyDeliveryReportController extends Controller
{
  protected ApDailyDeliveryReportService $service;

  public function __construct(ApDailyDeliveryReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Genera el reporte diario de entregas y facturación en JSON
   *
   * @param DailyDeliveryReportRequest $request
   * @return JsonResponse
   */
  public function index(DailyDeliveryReportRequest $request): JsonResponse
  {
    try {
      $fechaInicio = $request->input('fecha_inicio');
      $fechaFin = $request->input('fecha_fin');
      return $this->success($this->service->generate($fechaInicio, $fechaFin));
    } catch (\Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Exporta el reporte diario de entregas y facturación a Excel
   *
   * @param DailyDeliveryReportRequest $request
   * @return BinaryFileResponse
   */
  public function export(DailyDeliveryReportRequest $request): BinaryFileResponse
  {
    try {
      $fechaInicio = $request->input('fecha_inicio');
      $fechaFin = $request->input('fecha_fin');
      $report = $this->service->generate($fechaInicio, $fechaFin);

      $filename = 'Reporte_Entregas_' . str_replace('-', '_', $fechaInicio) . '_a_' . str_replace('-', '_', $fechaFin) . '.xlsx';

      return Excel::download(new DailyDeliveryReportExport($report), $filename);
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
