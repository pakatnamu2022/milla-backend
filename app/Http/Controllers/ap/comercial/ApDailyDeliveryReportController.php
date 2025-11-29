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
      $date = $request->input('date');
      $report = $this->service->generate($date);

      return response()->json($report, 200);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Error al generar el reporte',
        'error' => $e->getMessage(),
      ], 500);
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
      $date = $request->input('date');
      $report = $this->service->generate($date);

      $filename = 'Reporte_Entregas_' . str_replace('-', '_', $date) . '.xlsx';

      return Excel::download(new DailyDeliveryReportExport($report), $filename);
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
