<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\Reports\PurchaseOrderReceiptsReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\PurchaseOrderReceiptsReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderReceiptsReportController extends Controller
{
  protected PurchaseOrderReceiptsReportService $service;

  public function __construct(PurchaseOrderReceiptsReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de documentos electrónicos
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportElectronicDocuments(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'fecha_emision' => 'required|array|size:2',
      'fecha_emision.*' => 'required|date',
      'sunat_concept_currency_id' => 'nullable|integer',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Obtener datos del reporte
    $data = $this->service->getElectronicDocumentsReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_documentos_electronicos_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new PurchaseOrderReceiptsReportExport($data, 'Reporte de Documentos Electrónicos'),
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

    // Filtro requerido: rango de fechas de emisión
    $filters[] = [
      'column' => 'fecha_de_emision',
      'operator' => 'date_between',
      'value' => $validated['fecha_emision'],
    ];

    // Filtro opcional: moneda
    if (isset($validated['sunat_concept_currency_id'])) {
      $filters[] = [
        'column' => 'sunat_concept_currency_id',
        'operator' => '=',
        'value' => $validated['sunat_concept_currency_id'],
      ];
    }

    return $filters;
  }
}
