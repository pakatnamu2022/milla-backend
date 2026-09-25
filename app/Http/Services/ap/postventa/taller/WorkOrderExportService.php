<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Exports\ap\postventa\Reports\WorkOrderListExport;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WorkOrderExportService
{
  /**
   * Exporta órdenes de trabajo según los filtros proporcionados
   *
   * @param Request $request
   * @return mixed
   */
  public function export(Request $request)
  {
    $filters = $this->buildFilters($request);
    $title = $request->get('title', 'Reporte de Órdenes de Trabajo');

    // Construir la query con las relaciones necesarias
    $query = ApWorkOrder::with([
      'vehicle.model.family.brand',
      'vehicle.model',
      'advisor',
      'sede',
      'status',
      'items.typePlanning',
      'items.typeOperation',
      'creator',
      'typeCurrency',
      'invoiceTo',
      'advancesWorkOrder',
      'internalNote.electronicDocuments'
    ]);

    // Aplicar filtros
    foreach ($filters as $filter) {
      $column = $filter['column'];
      $operator = $filter['operator'];
      $value = $filter['value'];

      switch ($operator) {
        case '=':
          $query->where($column, $value);
          break;
        case 'like':
          $query->where($column, 'like', "%{$value}%");
          break;
        case 'in_or_equal':
          if (is_array($value)) {
            $query->whereIn($column, $value);
          } else {
            $query->where($column, $value);
          }
          break;
        case 'date_between':
          if (is_array($value) && count($value) === 2) {
            $query->whereDate($column, '>=', $value[0])
              ->whereDate($column, '<=', $value[1]);
          }
          break;
      }
    }

    // Obtener las órdenes de trabajo
    $workOrders = $query->get();

    // Crear el export y descargar
    $export = new WorkOrderListExport($workOrders, $title);

    return Excel::download($export, "{$title}.xlsx");
  }

  /**
   * Construye los filtros a partir del request
   *
   * @param Request $request
   * @return array
   */
  protected function buildFilters(Request $request): array
  {
    $filters = [];

    if ($request->filled('advisor_id')) {
      $filters[] = [
        'column' => 'advisor_id',
        'operator' => '=',
        'value' => $request->advisor_id
      ];
    }

    if ($request->filled('sede_id')) {
      $filters[] = [
        'column' => 'sede_id',
        'operator' => '=',
        'value' => $request->sede_id
      ];
    }

    if ($request->filled('status_id')) {
      $filters[] = [
        'column' => 'status_id',
        'operator' => 'in_or_equal',
        'value' => $request->status_id
      ];
    }

    if ($request->filled('opening_date')) {
      $filters[] = [
        'column' => 'opening_date',
        'operator' => 'date_between',
        'value' => $request->opening_date
      ];
    }

    if ($request->filled('estimated_delivery_date')) {
      $filters[] = [
        'column' => 'estimated_delivery_date',
        'operator' => 'date_between',
        'value' => $request->estimated_delivery_date
      ];
    }

    if ($request->filled('actual_delivery_date')) {
      $filters[] = [
        'column' => 'actual_delivery_date',
        'operator' => 'between',
        'value' => $request->actual_delivery_date
      ];
    }

    if ($request->filled('is_invoiced')) {
      $filters[] = [
        'column' => 'is_invoiced',
        'operator' => '=',
        'value' => $request->is_invoiced
      ];
    }

    if ($request->filled('currency_id')) {
      $filters[] = [
        'column' => 'currency_id',
        'operator' => '=',
        'value' => $request->currency_id
      ];
    }

    return $filters;
  }
}
