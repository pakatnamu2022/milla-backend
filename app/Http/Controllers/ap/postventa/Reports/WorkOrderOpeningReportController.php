<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\Reports\WorkOrderOpeningReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\WorkOrderOpeningReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WorkOrderOpeningReportController extends Controller
{
  protected WorkOrderOpeningReportService $service;

  public function __construct(WorkOrderOpeningReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de Órdenes de Trabajo por Apertura
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportWorkOrderOpenings(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'opening_date' => 'required|array|size:2',
      'opening_date.*' => 'required|date',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Obtener datos del reporte
    $data = $this->service->getWorkOrderOpeningsReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_apertura_ordenes_trabajo_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new WorkOrderOpeningReportExport($data, 'Reporte de Apertura de Órdenes de Trabajo'),
      $filename
    );
  }

  /**
   * Construye los filtros a partir de los parámetros validados
   *
   * @param array $validated
   * @return array
   */
  private function buildFilters(array $validated): array
  {
    $filters = [];

    // Filtro requerido: rango de fechas de apertura
    $filters[] = [
      'column' => 'opening_date',
      'operator' => 'between',
      'value' => $validated['opening_date'],
    ];

    // Filtro por sede de la OT
    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    return $filters;
  }
}
