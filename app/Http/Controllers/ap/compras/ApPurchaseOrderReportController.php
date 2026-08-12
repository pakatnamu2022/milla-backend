<?php

namespace App\Http\Controllers\ap\compras;

use App\Exports\ap\compras\PurchaseOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ap\compras\PurchaseOrderReportRequest;
use App\Http\Services\ap\compras\ApPurchaseOrderReportService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApPurchaseOrderReportController extends Controller
{
  protected ApPurchaseOrderReportService $service;

  public function __construct(ApPurchaseOrderReportService $service)
  {
    $this->service = $service;
  }

  public function export(PurchaseOrderReportRequest $request): BinaryFileResponse
  {
    try {
      $fechaInicio = $request->input('fecha_inicio');
      $fechaFin    = $request->input('fecha_fin');

      $data = $this->service->generate(
        $fechaInicio,
        $fechaFin,
        $request->input('sede_id')
      );

      $filename = 'Reporte_OC_' . str_replace('-', '_', $fechaInicio) . '_a_' . str_replace('-', '_', $fechaFin) . '.xlsx';

      return Excel::download(new PurchaseOrderReportExport($data, $fechaInicio, $fechaFin), $filename);
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
