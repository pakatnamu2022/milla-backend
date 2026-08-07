<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\taller\PartsReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\PartsReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PartsReportController extends Controller
{
  protected PartsReportService $service;

  public function __construct(PartsReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de Repuestos de Órdenes de Trabajo
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportParts(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'closing_date' => 'required|array|size:2',
      'closing_date.*' => 'required|date',
      'currency_id' => 'nullable|integer',
      'amounts_in_soles' => 'nullable|boolean',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Determinar si los montos deben estar en soles
    $amountsInSoles = $validated['amounts_in_soles'] ?? false;

    // Obtener datos del reporte
    $data = $this->service->getPartsReport($filters, $amountsInSoles);

    // Generar nombre del archivo
    $filename = 'reporte_repuestos_ot_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new PartsReportExport($data, 'Reporte de Repuestos OT', $amountsInSoles),
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

    // Filtro requerido: rango de fechas de cierre de OT
    $filters[] = [
      'column' => 'actual_delivery_date',
      'operator' => 'workOrderDateFilter',
      'value' => $validated['closing_date'],
    ];

    // Filtro por sede de la OT
    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    // Filtro por moneda de la OT
    if (isset($validated['currency_id'])) {
      $filters[] = [
        'column' => 'currency_id',
        'operator' => '=',
        'value' => $validated['currency_id'],
      ];
    }

    return $filters;
  }
}