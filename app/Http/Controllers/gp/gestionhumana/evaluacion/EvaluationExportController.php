<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Services\common\ExportService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use Illuminate\Http\Request;

class EvaluationExportController extends Controller
{
  protected $exportService;

  public function __construct(ExportService $exportService)
  {
    $this->exportService = $exportService;
  }

  /**
   * Exportar evaluaciones básico
   */
  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, Evaluation::class);
  }

  /**
   * Exportar con filtros específicos
   */
  public function exportFiltered(Request $request)
  {
    $request->validate([
      'format' => 'in:excel,pdf',
      'title' => 'string|max:255',
      'columns' => 'array',
      'status' => 'integer|in:0,1,2',
      'type_evaluation' => 'integer|in:0,1,2',
      'cycle_id' => 'integer',
      'period_id' => 'integer'
    ]);

    $filters = [];

    if ($request->filled('status')) {
      $filters[] = [
        'column' => 'status',
        'operator' => '=',
        'value' => $request->get('status')
      ];
    }

    if ($request->filled('type_evaluation')) {
      $filters[] = [
        'column' => 'typeEvaluation',
        'operator' => '=',
        'value' => $request->get('type_evaluation')
      ];
    }

    if ($request->filled('cycle_id')) {
      $filters[] = [
        'column' => 'cycle_id',
        'operator' => '=',
        'value' => $request->get('cycle_id')
      ];
    }

    if ($request->filled('period_id')) {
      $filters[] = [
        'column' => 'period_id',
        'operator' => '=',
        'value' => $request->get('period_id')
      ];
    }

    if ($request->filled('search')) {
      $filters[] = [
        'column' => 'name',
        'operator' => 'like',
        'value' => $request->get('search')
      ];
    }

    if ($request->filled('date_from')) {
      $filters[] = [
        'column' => 'start_date',
        'operator' => '>=',
        'value' => $request->get('date_from')
      ];
    }

    if ($request->filled('date_to')) {
      $filters[] = [
        'column' => 'end_date',
        'operator' => '<=',
        'value' => $request->get('date_to')
      ];
    }

    $options = [
      'title' => $request->get('title', 'Reporte de Evaluaciones'),
      'filters' => $filters,
      'columns' => $request->get('columns')
    ];

    $format = $request->get('format', 'excel');

    if ($format === 'pdf') {
      return $this->exportService->exportToPdf(Evaluation::class, $options);
    } else {
      return $this->exportService->exportToExcel(Evaluation::class, $options);
    }
  }

  /**
   * Exportar por estado específico
   */
  public function exportByStatus(Request $request, $status)
  {
    $statusNames = [
      0 => 'Programadas',
      1 => 'En Progreso',
      2 => 'Finalizadas'
    ];

    $options = [
      'title' => 'Evaluaciones ' . ($statusNames[$status] ?? 'Estado ' . $status),
      'filters' => [
        [
          'column' => 'status',
          'operator' => '=',
          'value' => $status
        ]
      ]
    ];

    $format = $request->get('format', 'excel');

    if ($format === 'pdf') {
      return $this->exportService->exportToPdf(Evaluation::class, $options);
    } else {
      return $this->exportService->exportToExcel(Evaluation::class, $options);
    }
  }

  /**
   * Exportar columnas específicas
   */
  public function exportCustomColumns(Request $request)
  {
    $request->validate([
      'columns' => 'required|array',
      'columns.*' => 'string',
      'format' => 'in:excel,pdf',
      'title' => 'string|max:255'
    ]);

    $options = [
      'title' => $request->get('title', 'Reporte Personalizado'),
      'columns' => $request->get('columns')
    ];

    $format = $request->get('format', 'excel');

    if ($format === 'pdf') {
      return $this->exportService->exportToPdf(Evaluation::class, $options);
    } else {
      return $this->exportService->exportToExcel(Evaluation::class, $options);
    }
  }
}
