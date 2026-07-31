<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\taller\WorkOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\TallerReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;

class TallerReportController extends Controller
{
  protected TallerReportService $service;

  public function __construct(TallerReportService $service)
  {
    $this->service = $service;
  }

  /**
   * Exporta el reporte de Órdenes de Trabajo
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportWorkOrders(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'advisor_id' => 'nullable|integer',
      'status_id' => 'nullable|array',
      'status_id.*' => 'integer',
      'opening_date' => 'required|array|size:2',
      'opening_date.*' => 'required|date',
      'actual_delivery_date' => 'nullable|array',
      'actual_delivery_date.*' => 'date',
      'is_invoiced' => 'nullable|boolean',
      'currency_id' => 'nullable|integer',
      'vehicle_plate' => 'nullable|string',
      'amounts_in_soles' => 'nullable|boolean',
    ]);

    // Construir filtros
    $filters = $this->buildFilters($validated);

    // Determinar si los montos deben estar en soles
    $amountsInSoles = $validated['amounts_in_soles'] ?? false;

    // TEMPORAL: Ejecutar comando para actualizar fechas de entrega antes de generar el reporte
    // TODO: Remover esto cuando se arregle la reportería
    Artisan::call('work-orders:update-delivery-dates', ['--force' => true]);

    // Obtener datos del reporte
    $data = $this->service->getWorkOrdersReport($filters, $amountsInSoles);

    // Generar nombre del archivo
    $filename = 'reporte_ordenes_trabajo_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new WorkOrderReportExport($data, 'Reporte de Órdenes de Trabajo', $amountsInSoles),
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

    // Filtro requerido: rango de fechas de emisión de documentos (comprobantes)
    $filters[] = [
      'column' => 'fecha_de_emision',
      'operator' => 'documentDateFilter',
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