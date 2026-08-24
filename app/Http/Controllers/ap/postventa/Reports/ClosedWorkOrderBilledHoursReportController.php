<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\Reports\ClosedWorkOrderBilledHoursReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\ClosedWorkOrderBilledHoursReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;

class ClosedWorkOrderBilledHoursReportController extends Controller
{
  protected ClosedWorkOrderBilledHoursReportService $service;

  public function __construct(ClosedWorkOrderBilledHoursReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de Horas Facturadas de Órdenes de Trabajo Cerradas
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function export(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'date_range' => 'required|array|size:2',
      'date_range.*' => 'required|date',
    ]);

    // Construir filtros
    $filters = [];

    // Filtro requerido: rango de fechas de finalización de trabajos
    $filters[] = [
      'column' => 'actual_end_datetime',
      'operator' => 'date_between',
      'value' => $validated['date_range'],
    ];

    // Filtro por sede
    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    // TEMPORAL: Ejecutar comando para actualizar fechas de entrega antes de generar el reporte
    // TODO: Remover esto cuando se arregle la reportería
    Artisan::call('work-orders:update-delivery-dates', ['--force' => true]);

    // Obtener datos del reporte
    $data = $this->service->getClosedWorkOrderBilledHoursReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_horas_facturadas_ot_cerradas_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel con tres hojas (summary, detail, consolidado OTs)
    return Excel::download(
      new ClosedWorkOrderBilledHoursReportExport($data, $filters),
      $filename
    );
  }
}
