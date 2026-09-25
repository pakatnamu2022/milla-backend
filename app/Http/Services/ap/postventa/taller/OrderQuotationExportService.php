<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Exports\ap\postventa\Reports\OrderQuotationListExport;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrderQuotationExportService
{
  /**
   * Exporta cotizaciones según los filtros proporcionados
   *
   * @param Request $request
   * @return mixed
   */
  public function export(Request $request)
  {
    $filters = $this->buildFilters($request);
    $title = $request->get('title', 'Reporte de Cotizaciones');

    // Construir la query con las relaciones necesarias
    $query = ApOrderQuotations::with([
      'vehicle.model.family.brand',
      'vehicle.model',
      'client',
      'sede',
      'status',
      'typeCurrency',
      'createdBy',
      'discardedBy',
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
            $query->whereBetween($column, $value);
          }
          break;
        case 'between':
          if (is_array($value) && count($value) === 2) {
            $query->whereBetween($column, $value);
          }
          break;
      }
    }

    // Obtener las cotizaciones
    $quotations = $query->get();

    // Crear el export y descargar
    $export = new OrderQuotationListExport($quotations, $title);

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

    if ($request->filled('area_id')) {
      $filters[] = [
        'column' => 'area_id',
        'operator' => '=',
        'value' => $request->area_id
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

    if ($request->filled('quotation_date')) {
      $filters[] = [
        'column' => 'quotation_date',
        'operator' => 'date_between',
        'value' => $request->quotation_date
      ];
    }

    if ($request->filled('estimated_delivery_date')) {
      $filters[] = [
        'column' => 'expiration_date',
        'operator' => 'date_between',
        'value' => $request->estimated_delivery_date
      ];
    }

    if ($request->filled('actual_delivery_date')) {
      $filters[] = [
        'column' => 'collection_date',
        'operator' => 'between',
        'value' => $request->actual_delivery_date
      ];
    }

    if ($request->filled('currency_id')) {
      $filters[] = [
        'column' => 'currency_id',
        'operator' => '=',
        'value' => $request->currency_id
      ];
    }

    if ($request->filled('quotation_number')) {
      $filters[] = [
        'column' => 'quotation_number',
        'operator' => 'like',
        'value' => $request->quotation_number
      ];
    }

    if ($request->filled('vehicle_plate')) {
      $filters[] = [
        'column' => 'vehicle_plate',
        'operator' => 'like',
        'value' => $request->vehicle_plate
      ];
    }

    return $filters;
  }
}