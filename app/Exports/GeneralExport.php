<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

//use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  WithColumnWidths,
  WithTitle,
  WithEvents
{
  protected $data;
  protected $columns;
  protected $title;
  protected $styles;

  public function __construct($data, $columns, $title = 'Reporte', $styles = [])
  {
    $this->data = collect($data);
    $this->columns = $columns;
    $this->title = $title;
    $this->styles = !empty($styles) ? $styles : $this->getDefaultStyles();
  }

  public function collection()
  {
    return $this->data;
  }

  public function headings(): array
  {
    $headings = [];
    foreach ($this->columns as $key => $column) {
      $headings[] = is_array($column) ? $column['label'] : $column;
    }
    return $headings;
  }

  public function map($row): array
  {
    $mapped = [];
    foreach ($this->columns as $key => $column) {
      $value = is_array($row) ? ($row[$key] ?? '') : data_get($row, $key, '');

      if (is_array($column) && isset($column['formatter'])) {
        $value = $this->formatValue($value, $column['formatter']);
      }

      $mapped[] = $value;
    }
    return $mapped;
  }

  protected function formatValue($value, $formatter)
  {
    if (is_null($value) || $value === '') return '';

    switch ($formatter) {
      case 'currency':
        return is_numeric($value) ? '$' . number_format($value, 2) : $value;
      case 'percentage':
        return is_numeric($value) ? $value : $value; // Excel lo formateará
      case 'date':
        if ($value instanceof \Carbon\Carbon) {
          return $value->format('d/m/Y');
        }
        if (is_string($value) && strtotime($value)) {
          return date('d/m/Y', strtotime($value));
        }
        return $value;
      case 'datetime':
        if ($value instanceof \Carbon\Carbon) {
          return $value->format('d/m/Y H:i:s');
        }
        if (is_string($value) && strtotime($value)) {
          return date('d/m/Y H:i:s', strtotime($value));
        }
        return $value;
      case 'number':
        return is_numeric($value) ? number_format($value) : $value;
      case 'boolean':
        if (is_bool($value)) {
          return $value ? 'Sí' : 'No';
        }
        return in_array(strtolower($value), ['1', 'true', 'yes', 'sí']) ? 'Sí' : 'No';
      default:
        return $value;
    }
  }

  public function styles(Worksheet $sheet)
  {
    return $this->styles;
  }

  public function columnWidths(): array
  {
    $widths = [];
    $columnLetter = 'A';

    foreach ($this->columns as $key => $column) {
      $width = 15; // Default

      if (is_array($column) && isset($column['width'])) {
        $width = $column['width'];
      } else if (is_array($column)) {
        // Auto-calcular basado en el label
        $labelLength = strlen($column['label'] ?? '');
        $width = max(12, min($labelLength * 1.2, 40));
      }

      $widths[$columnLetter] = $width;
      $columnLetter++;
    }

    return $widths;
  }

  public function title(): string
  {
    return $this->title;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Congelar primera fila
        $sheet->freezePane('A2');

        // Auto filtro
        $lastColumn = chr(ord('A') + count($this->columns) - 1);
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        // Altura de filas
        $sheet->getRowDimension(1)->setRowHeight(25);
      },
    ];
  }

  protected function getDefaultStyles()
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'size' => 12,
          'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
          'fillType' => 'solid',
          'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
          'horizontal' => 'center',
          'vertical' => 'center'
        ]
      ],
      'A2:Z1000' => [
        'alignment' => [
          'vertical' => 'center'
        ],
        'borders' => [
          'allBorders' => [
            'borderStyle' => 'thin',
            'color' => ['rgb' => 'D4D4D4']
          ]
        ]
      ]
    ];
  }
}
