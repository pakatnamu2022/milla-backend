<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\Reports\ElectronicDocumentsReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\ElectronicDocumentsReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ElectronicDocumentsReportController extends Controller
{
  protected ElectronicDocumentsReportService $service;

  public function __construct(ElectronicDocumentsReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de Documentos Electrónicos (solo cabecera con totales)
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function export(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'area_id' => 'required|array|min:1',
      'area_id.*' => 'required|integer',
      'fecha_de_emision' => 'required|array|size:2',
      'fecha_de_emision.*' => 'required|date',
      'seriesModel$sede_id' => 'nullable|integer',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Obtener datos del reporte
    $data = $this->service->getElectronicDocumentReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_documentos_electronicos_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new ElectronicDocumentsReportExport($data, 'Reporte de Documentos Electrónicos'),
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

    // Filtro requerido: áreas
    $filters[] = [
      'column' => 'area_id',
      'operator' => 'in',
      'value' => $validated['area_id'],
    ];

    // Filtro requerido: rango de fechas de emisión
    // Usar whereDate para filtrar por fecha sin hora
    if (isset($validated['fecha_de_emision']) && count($validated['fecha_de_emision']) === 2) {
      $filters[] = [
        'column' => 'fecha_de_emision',
        'operator' => 'dateRange',
        'value' => $validated['fecha_de_emision'],
      ];
    }

    // Filtro opcional: sede (el frontend envía seriesModel$sede_id)
    if (isset($validated['seriesModel$sede_id'])) {
      $filters[] = [
        'column' => 'seriesModel.sede_id',
        'operator' => '=',
        'value' => $validated['seriesModel$sede_id'],
      ];
    }

    return $filters;
  }
}
