<?php

namespace App\Http\Services;

use App\Exports\GeneralExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ExportService
{
  public function exportToExcel($modelClass, $options = [])
  {
    $model = app($modelClass);
    $data = $this->getData($model, $options);
    $columns = $this->getColumns($model, $options['columns'] ?? null);
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
    $columns = $this->getColumns($model, $options['columns'] ?? null);
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

    // Auto orientación basada en número de columnas
    if (count($columns) > 6) {
      $pdf->setPaper('a4', 'landscape');
    } else {
      $pdf->setPaper('a4', 'portrait');
    }

    $filename = $this->generateFilename($title, 'pdf');

    return $pdf->download($filename);
  }

  public function exportFromRequest($request, $modelClass)
  {
    $format = $request->get('format', 'excel');
    $filters = $this->buildFiltersFromRequest($request);

    $options = [
      'title' => $request->get('title', 'Reporte'),
      'filters' => $filters,
      'columns' => $request->get('columns'),
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

  protected function getColumns($model, $requestedColumns = null)
  {
    $availableColumns = $model->getReportableColumns();

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

  protected function buildFiltersFromRequest($request)
  {
    $filters = [];

    // Filtros comunes
    if ($request->filled('search')) {
      $filters[] = [
        'column' => 'name',
        'operator' => 'like',
        'value' => $request->get('search')
      ];
    }

    if ($request->filled('status')) {
      $filters[] = [
        'column' => 'status',
        'operator' => '=',
        'value' => $request->get('status')
      ];
    }

    if ($request->filled('date_from')) {
      $filters[] = [
        'column' => 'created_at',
        'operator' => '>=',
        'value' => $request->get('date_from')
      ];
    }

    if ($request->filled('date_to')) {
      $filters[] = [
        'column' => 'created_at',
        'operator' => '<=',
        'value' => $request->get('date_to')
      ];
    }

    return $filters;
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
