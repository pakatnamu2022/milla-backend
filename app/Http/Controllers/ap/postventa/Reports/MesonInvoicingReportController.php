<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\meson\MesonInvoicingReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\MesonInvoicingReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MesonInvoicingReportController extends Controller
{
  protected MesonInvoicingReportService $service;

  public function __construct(MesonInvoicingReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de facturación de Cotizaciones de Mesón
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportMesonInvoicing(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'fecha_emision' => 'required|array|size:2',
      'fecha_emision.*' => 'required|date',
      'document_type_id' => 'nullable|integer',
      'quotation_number' => 'nullable|string',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Obtener datos del reporte
    $reportData = $this->service->getMesonInvoicingReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_facturacion_meson_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new MesonInvoicingReportExport(
        $reportData['report_data'],
        'Reporte de Facturación Mesón'
      ),
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

    // Filtro requerido: rango de fechas de emisión de documentos
    $filters[] = [
      'column' => 'fecha_de_emision',
      'operator' => 'documentDateFilter',
      'value' => $validated['fecha_emision'],
    ];

    // Filtro por sede de la cotización
    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    // Filtro por tipo de documento (Factura o Boleta)
    if (isset($validated['document_type_id'])) {
      $filters[] = [
        'column' => 'document_type_id',
        'operator' => '=',
        'value' => $validated['document_type_id'],
      ];
    }

    // Filtro por número de cotización
    if (isset($validated['quotation_number'])) {
      $filters[] = [
        'column' => 'quotation_number',
        'operator' => 'like',
        'value' => $validated['quotation_number'],
      ];
    }

    return $filters;
  }
}