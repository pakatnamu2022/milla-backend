<?php

namespace App\Http\Controllers\ap\compras;

use App\Exports\ap\compras\PurchaseOrderReportMultiExport;
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

      $result = $this->service->generate(
        $fechaInicio,
        $fechaFin,
        $request->input('sede_id')
      );

      $filename = 'Reporte_OC_'
        . ($fechaInicio ? str_replace('-', '_', $fechaInicio) : 'todos')
        . '_a_'
        . ($fechaFin ? str_replace('-', '_', $fechaFin) : 'todos')
        . '.xlsx';

      return Excel::download(
        new PurchaseOrderReportMultiExport($result['orders'], $fechaInicio, $fechaFin, $result['cuentasPorPagar']),
        $filename
      );
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
