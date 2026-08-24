<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\Reports\WorkedHoursBySedeReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\WorkedHoursBySedeReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WorkedHoursBySedeReportController extends Controller
{
  protected WorkedHoursBySedeReportService $service;

  public function __construct(WorkedHoursBySedeReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de Horas Trabajadas por Sede
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

    // Obtener datos del reporte (retorna array con 'summary' y 'detail')
    $data = $this->service->getWorkedHoursBySedeReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_horas_trabajadas_sede_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel con dos hojas
    return Excel::download(
      new WorkedHoursBySedeReportExport($data),
      $filename
    );
  }
}
