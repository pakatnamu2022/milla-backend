<?php

namespace App\Http\Controllers\ap\postventa\Reports;

use App\Exports\ap\postventa\Reports\WorkShopReportExport;
use App\Exports\ap\postventa\Reports\ClosedWorkOrdersByVehicleExport;
use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\Reports\WorkShopReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WorkShopReportController extends Controller
{
  protected WorkShopReportService $service;

  public function __construct(WorkShopReportService $service)
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
      'fecha_de_emision' => 'required|array|size:2',
      'fecha_de_emision.*' => 'required|date',
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
      new WorkShopReportExport($data, 'Reporte de Órdenes de Trabajo', $amountsInSoles),
      $filename
    );
  }

  /**
   * Exporta el reporte de Órdenes de Trabajo Cerradas por Vehículo (última OT por VIN)
   *
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function exportClosedWorkOrdersByVehicle(Request $request)
  {
    // Validar parámetros
    $validated = $request->validate([
      'sede_id' => 'nullable|integer',
      'advisor_id' => 'nullable|integer',
      'closing_date' => 'required|array|size:2',
      'closing_date.*' => 'required|date',
    ]);

    // Construir filtros
    $filters = $this->buildClosedWorkOrderFilters($validated);

    // Obtener datos del reporte
    $data = $this->service->getClosedWorkOrdersByVehicleReport($filters);

    // Generar nombre del archivo
    $filename = 'reporte_ordenes_cerradas_por_vehiculo_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    // Exportar a Excel
    return Excel::download(
      new ClosedWorkOrdersByVehicleExport($data, 'Reporte de Órdenes Cerradas por Vehículo'),
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
      'value' => $validated['fecha_de_emision'],
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

  /**
   * Construye los filtros para el reporte de órdenes cerradas por vehículo
   *
   * @param array $validated
   * @return array
   */
  private function buildClosedWorkOrderFilters(array $validated): array
  {
    $filters = [];

    // Filtro requerido: rango de fechas de cierre
    $filters[] = [
      'column' => 'actual_delivery_date',
      'operator' => 'closingDateFilter',
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

    // Filtro por asesor
    if (isset($validated['advisor_id'])) {
      $filters[] = [
        'column' => 'advisor_id',
        'operator' => '=',
        'value' => $validated['advisor_id'],
      ];
    }

    return $filters;
  }
}
