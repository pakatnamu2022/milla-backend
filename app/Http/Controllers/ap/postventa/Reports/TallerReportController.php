<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\taller\WorkOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\TallerReportService;
use App\Models\ap\ApMasters;
use Illuminate\Http\Request;
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

    // Filtrar solo OTs cerradas por defecto, a menos que se especifique otro status_id
    if (isset($validated['status_id'])) {
      $filters[] = [
        'column' => 'status_id',
        'operator' => 'in_or_equal',
        'value' => $validated['status_id'],
      ];
    } else {
      $filters[] = [
        'column' => 'status_id',
        'operator' => '=',
        'value' => ApMasters::CLOSED_WORK_ORDER_ID,
      ];
    }

    // Filtro requerido: rango de fechas de apertura
    $filters[] = [
      'column' => 'opening_date',
      'operator' => 'date_between',
      'value' => $validated['opening_date'],
    ];

    if (isset($validated['sede_id'])) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $validated['sede_id'],
      ];
    }

    if (isset($validated['advisor_id'])) {
      $filters[] = [
        'column' => 'advisor_id',
        'operator' => '=',
        'value' => $validated['advisor_id'],
      ];
    }

    if (isset($validated['actual_delivery_date']) && count($validated['actual_delivery_date']) === 2) {
      $filters[] = [
        'column' => 'actual_delivery_date',
        'operator' => 'between',
        'value' => $validated['actual_delivery_date'],
      ];
    }

    if (isset($validated['is_invoiced'])) {
      $filters[] = [
        'column' => 'is_invoiced',
        'operator' => '=',
        'value' => $validated['is_invoiced'],
      ];
    }

    if (isset($validated['currency_id'])) {
      $filters[] = [
        'column' => 'currency_id',
        'operator' => '=',
        'value' => $validated['currency_id'],
      ];
    }

    if (isset($validated['vehicle_plate'])) {
      $filters[] = [
        'column' => 'vehicle_plate',
        'operator' => 'like',
        'value' => $validated['vehicle_plate'],
      ];
    }

    return $filters;
  }
}