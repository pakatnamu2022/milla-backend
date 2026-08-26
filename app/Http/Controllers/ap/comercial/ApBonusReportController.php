<?php

namespace App\Http\Controllers\ap\comercial;

use App\Exports\ap\comercial\BonusReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\BonusReportRequest;
use App\Http\Services\ap\comercial\ApBonusReportService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApBonusReportController extends Controller
{
  protected ApBonusReportService $service;

  public function __construct(ApBonusReportService $service)
  {
    $this->service = $service;
  }

  public function export(BonusReportRequest $request): BinaryFileResponse
  {
    try {
      $fechaInicio = $request->input('fecha_inicio');
      $fechaFin    = $request->input('fecha_fin');

      $groupedData = $this->service->generate(
        $fechaInicio,
        $fechaFin,
        $request->input('sede_id')
      );

      $suffix = ($fechaInicio && $fechaFin)
        ? str_replace('-', '_', $fechaInicio) . '_a_' . str_replace('-', '_', $fechaFin)
        : now()->format('Y_m_d');

      $filename = 'Reporte_Bonos_' . $suffix . '.xlsx';

      return Excel::download(new BonusReportExport($groupedData), $filename);
    } catch (\Exception $e) {
      abort(500, 'Error al exportar el reporte: ' . $e->getMessage());
    }
  }
}
