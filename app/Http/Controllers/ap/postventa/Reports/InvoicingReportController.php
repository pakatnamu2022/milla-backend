<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\taller\InvoicingReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\InvoicingReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InvoicingReportController extends Controller
{
  protected InvoicingReportService $service;

  public function __construct(InvoicingReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de facturación de Órdenes de Trabajo
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportInvoicing(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'fecha_emision' => 'required|array|size:2',
      'fecha_emision.*' => 'required|date',
      'document_type_id' => 'nullable|integer',
      'is_advance_payment' => 'nullable|boolean',
      'work_order_correlative' => 'nullable|string',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Obtener datos del reporte
    $reportData = $this->service->getInvoicingReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_facturacion_ot_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new InvoicingReportExport(
        $reportData['data'],
        $reportData['summary'],
        'Reporte de Facturación OT'
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

    // Filtro por sede de la OT
    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    // Filtro por número de OT
    if (isset($validated['work_order_correlative'])) {
      $filters[] = [
        'column' => 'correlative',
        'operator' => 'like',
        'value' => $validated['work_order_correlative'],
      ];
    }

    return $filters;
  }
}