<?php


namespace App\Exports\gp\gh\evaluation;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class EvaluationPersonResultExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  WithColumnWidths,
  WithTitle,
  WithCustomStartCell
{
  protected $filters;
  protected $columns;
  protected $model;

  public function __construct($filters = [], $columns = null)
  {
    $this->filters = $filters;
    $this->columns = $columns;
    $this->model = new EvaluationPersonResult();
  }

  /**
   * @return \Illuminate\Support\Collection
   */
  public function collection()
  {
    return $this->model->getReportData($this->columns, $this->filters);
  }

  /**
   * @return array
   */
  public function headings(): array
  {
    $reportColumns = $this->model->getReportableColumns();
    $columns = $this->columns ?: array_keys($reportColumns);

    $headings = [];
    foreach ($columns as $column) {
      $headings[] = $reportColumns[$column]['label'] ?? ucfirst(str_replace('_', ' ', $column));
    }

    return $headings;
  }

  /**
   * @param mixed $evaluation
   * @return array
   */
  public function map($evaluation): array
  {
    $reportColumns = $this->model->getReportableColumns();
    $columns = $this->columns ?: array_keys($reportColumns);

    $mapped = [];
    foreach ($columns as $column) {
      $value = $evaluation[$column] ?? '';
      $formatter = $reportColumns[$column]['formatter'] ?? null;

      $mapped[] = $this->formatValue($value, $formatter);
    }

    return $mapped;
  }

  /**
   * Formatear valores según el tipo
   */
  protected function formatValue($value, $formatter)
  {
    if (is_null($value)) return '';

    switch ($formatter) {
      case 'currency':
        return '$' . number_format($value, 2);
      case 'percentage':
        return $value; // Excel lo formateará como porcentaje
      case 'date':
        return $value instanceof \Carbon\Carbon ? $value->format('d/m/Y') :
          (\DateTime::createFromFormat('Y-m-d', $value) ?
            \DateTime::createFromFormat('Y-m-d', $value)->format('d/m/Y') : $value);
      case 'datetime':
        return $value instanceof \Carbon\Carbon ? $value->format('d/m/Y H:i:s') : $value;
      case 'number':
        return is_numeric($value) ? $value : $value;
      case 'boolean':
        return $value ? 'Sí' : 'No';
      default:
        return $value;
    }
  }

  /**
   * @param Worksheet $sheet
   * @return array
   */
  public function styles(Worksheet $sheet)
  {
    // Obtener estilos del modelo
    $styles = $this->model->getReportStyles();

    // Agregar altura de fila para el header
    $sheet->getRowDimension(3)->setRowHeight(25);

    // Freeze del header
    $sheet->freezePane('A4');

    // Aplicar filtros automáticos
    $lastColumn = $this->getLastColumn();
    $sheet->setAutoFilter("A3:{$lastColumn}3");

    return $styles;
  }

  /**
   * @return array
   */
  public function columnWidths(): array
  {
    $reportColumns = $this->model->getReportableColumns();
    $columns = $this->columns ?: array_keys($reportColumns);

    $widths = [];
    $columnLetter = 'A';

    foreach ($columns as $column) {
      $width = $reportColumns[$column]['width'] ?? 15;
      $widths[$columnLetter] = $width;
      $columnLetter++;
    }

    return $widths;
  }

  /**
   * @return string
   */
  public function title(): string
  {
    return 'Evaluaciones';
  }

  /**
   * @return string
   */
  public function startCell(): string
  {
    return 'A3'; // Empezar en A3 para dejar espacio para título
  }

  /**
   * Obtener la última columna utilizada
   */
  protected function getLastColumn()
  {
    $reportColumns = $this->model->getReportableColumns();
    $columns = $this->columns ?: array_keys($reportColumns);
    $columnCount = count($columns);

    return chr(ord('A') + $columnCount - 1);
  }
}
