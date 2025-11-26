<?php

namespace App\Http\Services\common;

use App\Exports\GeneralExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ExportService
{
  public function exportToExcel($modelClass, $options = [])
  {
    $model = app($modelClass);
    $data = $this->getData($model, $options);
    $columns = $this->getColumns($model, $options['columns'] ?? null, $options['context'] ?? []);
    $title = $options['title'] ?? 'Reporte';
    $styles = $options['styles'] ?? $model->getReportStyles();

    $filename = $this->generateFilename($title, 'xlsx');

    $export = new GeneralExport($data, $columns, $title, $styles);

    return Excel::download($export, $filename);
  }

  public function exportToPdf($modelClass, $options = [])
  {
    $model = app($modelClass);
    $data = $this->getData($model, $options);
    $columns = $this->getColumns($model, $options['columns'] ?? null, $options['context'] ?? []);
    $title = $options['title'] ?? 'Reporte';

    $viewData = [
      'data' => $data,
      'columns' => $columns,
      'title' => $title,
      'summary' => $options['summary'] ?? null,
      'getColumnClass' => function ($key) {
        if (str_contains($key, 'id')) return 'col-id';
        if (str_contains($key, 'name') || str_contains($key, 'nombre')) return 'col-name';
        if (str_contains($key, 'date') || str_contains($key, 'fecha')) return 'col-date';
        if (str_contains($key, 'status') || str_contains($key, 'estado')) return 'col-status';
        if (str_contains($key, 'percentage') || str_contains($key, 'porcentaje')) return 'col-percentage';
        if (str_contains($key, 'evaluation') || str_contains($key, 'Evaluation')) return 'col-boolean';
        return '';
      }
    ];

    $pdf = Pdf::loadView('exports.pdf-template', $viewData);

    // Auto-ajuste dinámico de papel y orientación basado en número de columnas
    $columnCount = count($columns);

    if ($columnCount <= 5) {
      // Pocas columnas: A4 vertical
      $pdf->setPaper('a4', 'portrait');
    } elseif ($columnCount <= 8) {
      // Columnas moderadas: A4 horizontal
      $pdf->setPaper('a4', 'landscape');
    } elseif ($columnCount <= 12) {
      // Muchas columnas: A3 horizontal
      $pdf->setPaper('a3', 'landscape');
    } else {
      // Demasiadas columnas: A2 horizontal (o el más grande disponible)
      $pdf->setPaper([0, 0, 1190.55, 1683.78], 'landscape'); // A2 en puntos
    }

    $filename = $this->generateFilename($title, 'pdf');

    return $pdf->download($filename);
  }

  public function exportFromRequest($request, $modelClass)
  {
    $format = $request->get('format', 'excel');
    $filters = $this->buildFiltersFromRequest($request, $modelClass);

    // Construir contexto desde el request para el filtrado de columnas
    $context = [];
    if ($request->filled('type')) {
      $context['type'] = $request->get('type');
    }

    $options = [
      'title' => $request->get('title', 'Reporte'),
      'filters' => $filters,
      'columns' => $request->get('columns'),
      'context' => $context,
      'summary' => $this->generateSummary($modelClass, $filters)
    ];

    if ($format === 'pdf') {
      return $this->exportToPdf($modelClass, $options);
    } else {
      return $this->exportToExcel($modelClass, $options);
    }
  }

  protected function getData($model, $options)
  {
    $filters = $options['filters'] ?? [];
    $data = $model->getReportData($filters);

    // Procesar datos si el modelo tiene método personalizado
    if (method_exists($model, 'processReportData')) {
      $data = $model->processReportData($data);
    }

    return $data;
  }

  protected function getColumns($model, $requestedColumns = null, $context = [])
  {
    $availableColumns = $model->getReportableColumns();

    // Si el modelo tiene método para filtrar columnas según contexto, usarlo
    if (method_exists($model, 'filterReportColumns')) {
      $availableColumns = $model->filterReportColumns($availableColumns, $context);
    }

    // Si se solicitaron columnas específicas, filtrar
    if ($requestedColumns && is_array($requestedColumns)) {
      $columns = [];
      foreach ($requestedColumns as $column) {
        if (isset($availableColumns[$column])) {
          $columns[$column] = $availableColumns[$column];
        }
      }
      return $columns;
    }

    return $availableColumns;
  }

  protected function buildFiltersFromRequest($request, $modelClass = null)
  {
    $filters = [];

    // Si se proporciona el modelo, intentar obtener los filtros definidos
    if ($modelClass) {
      $model = app($modelClass);

      // Verificar si el modelo tiene filtros definidos
      if (defined("$modelClass::filters")) {
        $modelFilters = $modelClass::filters;

        // Iterar sobre los filtros definidos en el modelo
        foreach ($modelFilters as $filterKey => $filterOperator) {
          // Si es un filtro de búsqueda (array de columnas)
          if ($filterKey === 'search' && is_array($filterOperator)) {
            if ($request->filled('search')) {
              // Aplicar búsqueda OR en todas las columnas definidas
              // Nota: Este caso requiere lógica especial, por ahora lo saltamos
              // y dejamos que el BaseService lo maneje
              continue;
            }
          }
          // Si el parámetro existe en el request
          elseif ($request->filled($filterKey)) {
            $value = $request->get($filterKey);

            // Determinar el operador basado en el tipo de filtro
            if ($filterOperator === 'between') {
              // Para filtros between, esperar un array [from, to] o [0 => from, 1 => to]
              if (is_array($value) && count($value) === 2) {
                // Reindexar el array para asegurar que sea [0, 1]
                $betweenValues = array_values($value);
                $filters[] = [
                  'column' => $filterKey,
                  'operator' => 'between',
                  'value' => $betweenValues
                ];
              }
            } elseif ($filterOperator === 'like') {
              $filters[] = [
                'column' => $filterKey,
                'operator' => 'like',
                'value' => $value
              ];
            } elseif ($filterOperator === 'in') {
              $filters[] = [
                'column' => $filterKey,
                'operator' => 'in',
                'value' => is_array($value) ? $value : [$value]
              ];
            } else {
              // Para operadores como '=', '>', '<', '>=', '<=', '!='
              $filters[] = [
                'column' => $filterKey,
                'operator' => $filterOperator,
                'value' => $value
              ];
            }
          }
        }
      }
    }

    // Mantener compatibilidad con filtros genéricos legacy
    if ($request->filled('date_from') && !$this->filterExists($filters, 'date_from')) {
      $filters[] = [
        'column' => 'created_at',
        'operator' => '>=',
        'value' => $request->get('date_from')
      ];
    }

    if ($request->filled('date_to') && !$this->filterExists($filters, 'date_to')) {
      $filters[] = [
        'column' => 'created_at',
        'operator' => '<=',
        'value' => $request->get('date_to')
      ];
    }

    return $filters;
  }

  protected function filterExists($filters, $column)
  {
    foreach ($filters as $filter) {
      if ($filter['column'] === $column) {
        return true;
      }
    }
    return false;
  }

  protected function generateSummary($modelClass, $filters = [])
  {
    $model = app($modelClass);
    $data = $model->getReportData($filters);

    $summary = [
      'Total Registros' => $data->count(),
      'Fecha Generación' => Carbon::now()->format('d/m/Y H:i:s')
    ];

    // Si el modelo tiene método para generar resumen personalizado
    if (method_exists($model, 'generateReportSummary')) {
      $customSummary = $model->generateReportSummary($data);
      $summary = array_merge($summary, $customSummary);
    }

    return $summary;
  }

  protected function generateFilename($title, $extension)
  {
    $slug = \Str::slug($title);
    $timestamp = Carbon::now()->format('Y-m-d_H-i-s');

    return "{$slug}_{$timestamp}.{$extension}";
  }
}
