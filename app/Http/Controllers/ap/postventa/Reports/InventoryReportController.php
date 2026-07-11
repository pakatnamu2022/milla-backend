<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\InventoryOutputReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\InventoryOutputReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReportController extends Controller
{
  protected InventoryOutputReportService $service;

  public function __construct(InventoryOutputReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de salidas de productos (Inventario)
   * Consolida salidas de Taller y Repuestos
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportInventoryOutputs(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'invoice_date' => 'nullable|array',
      'invoice_date.*' => 'date',
      'product_id' => 'nullable|integer',
      'area' => 'nullable|in:TALLER,REPUESTOS',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Obtener datos del reporte
    $data = $this->service->getInventoryOutputReport($filters);

    // Aplicar filtro de área si existe (post-procesamiento)
    if (isset($validated['area'])) {
      $data = $data->filter(function ($item) use ($validated) {
        return $item['area'] === $validated['area'];
      })->values();
    }

    // Generar nombre del archivo
    $filename = 'reporte_salidas_inventario_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new InventoryOutputReportExport($data, 'Reporte de Salidas de Inventario'),
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

    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    if (isset($validated['invoice_date']) && count($validated['invoice_date']) === 2) {
      $filters[] = [
        'column' => 'invoice_date',
        'operator' => 'date_between',
        'value' => $validated['invoice_date'],
      ];
    }

    if (isset($validated['product_id'])) {
      $filters[] = [
        'column' => 'product_id',
        'operator' => '=',
        'value' => $validated['product_id'],
      ];
    }

    return $filters;
  }
}