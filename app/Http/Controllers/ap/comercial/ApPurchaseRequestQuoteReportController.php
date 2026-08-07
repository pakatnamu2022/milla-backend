<?php

namespace App\Http\Controllers\ap\comercial;

use App\Exports\ap\comercial\PurchaseRequestQuoteReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\PurchaseRequestQuoteReportRequest;
use App\Http\Services\ap\comercial\ApPurchaseRequestQuoteReportService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApPurchaseRequestQuoteReportController extends Controller
{
  protected ApPurchaseRequestQuoteReportService $service;

  public function __construct(ApPurchaseRequestQuoteReportService $service)
  {
    $this->service = $service;
  }

  public function export(PurchaseRequestQuoteReportRequest $request): BinaryFileResponse
  {
    try {
      $fechaInicio = $request->input('fecha_inicio');
      $fechaFin = $request->input('fecha_fin');

      $data = $this->service->generate(
        $fechaInicio,
        $fechaFin,
        $request->input('sede_id'),
        $request->input('brand_id'),
        $request->input('family_id'),
        $request->input('ap_models_vn_id')
      );

      $filename = 'Reporte_Cotizaciones_' . str_replace('-', '_', $fechaInicio) . '_a_' . str_replace('-', '_', $fechaFin) . '.xlsx';

      return Excel::download(new PurchaseRequestQuoteReportExport($data, $fechaInicio, $fechaFin), $filename);
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
