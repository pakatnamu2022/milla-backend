<?php

namespace App\Http\Controllers\ap\comercial;

use App\Exports\GeneralExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\comercial\ApAdvancePaymentReportService;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApAdvancePaymentReportController extends Controller
{
  protected ApAdvancePaymentReportService $service;

  public function __construct(ApAdvancePaymentReportService $service)
  {
    $this->service = $service;
  }

  public function export(Request $request): BinaryFileResponse
  {
    try {
      $title = 'Reporte de Anticipos';
      $data = $this->service->generate($request);

      return Excel::download(
        new GeneralExport(
          $data,
          $this->service->columns(),
          $title,
          (new ElectronicDocument())->getReportStyles(),
          $this->service->colorRules()
        ),
        'Reporte_Anticipos_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
      );
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
