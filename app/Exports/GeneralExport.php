<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected $data;
  protected $columns;
  protected $title;
  protected $styles;
  protected $cellColorRules;

  public function __construct($data, $columns, $title = 'Reporte', $styles = [], $cellColorRules = [])
  {
    $this->data = collect($data);
    $this->columns = $columns;
    $this->title = $title;
    $this->styles = !empty($styles) ? $styles : $this->getDefaultStyles();
    $this->cellColorRules = $cellColorRules;
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
      // Si hay un accessor definido, usarlo
      if (is_array($column) && isset($column['accessor'])) {
        $accessor = $column['accessor'];
        // Llamar al accessor del modelo si existe
        if (is_object($row) && method_exists($row, $accessor)) {
          $value = $row->$accessor();
        } else {
          $value = is_array($row) ? ($row[$key] ?? '') : data_get($row, $key, '');
        }
      } else {
        $value = is_array($row) ? ($row[$key] ?? '') : data_get($row, $key, '');
      }

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
    // If styles is in custom format (from model), convert to PhpSpreadsheet format
    if (isset($this->styles['headerBackgroundColor']) || isset($this->styles['headerFontColor'])) {
      return $this->convertCustomStylesToPhpSpreadsheet($this->styles);
    }

    return $this->styles;
  }

  protected function convertCustomStylesToPhpSpreadsheet(array $customStyles): array
  {
    $styles = [];

    // Header row styles (row 1)
    $headerStyle = [];

    if (isset($customStyles['headerBold']) && $customStyles['headerBold']) {
      $headerStyle['font']['bold'] = true;
    }

    if (isset($customStyles['headerFontSize'])) {
      $headerStyle['font']['size'] = $customStyles['headerFontSize'];
    }

    if (isset($customStyles['headerFontColor'])) {
      $headerStyle['font']['color'] = ['rgb' => $customStyles['headerFontColor']];
    }

    if (isset($customStyles['headerBackgroundColor'])) {
      $headerStyle['fill'] = [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => $customStyles['headerBackgroundColor']]
      ];
    }

    $headerStyle['alignment'] = [
      'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    ];

    if (!empty($headerStyle)) {
      $styles[1] = $headerStyle;
    }

    // Body styles (from row 2 onwards)
    $bodyStyle = [];

    if (isset($customStyles['bodyFontSize'])) {
      $bodyStyle['font']['size'] = $customStyles['bodyFontSize'];
    }

    $bodyStyle['alignment'] = [
      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    ];

    $bodyStyle['borders'] = [
      'allBorders' => [
        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        'color' => ['rgb' => 'D4D4D4']
      ]
    ];

    if (!empty($bodyStyle)) {
      $styles['A2:Z1000'] = $bodyStyle;
    }

    return $styles;
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

        // Altura de filas del header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Ajustar alineación vertical sin wrap text
        $lastRow = $sheet->getHighestRow();
        $alignment = $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment();
        $alignment->setVertical('center');
        $alignment->setWrapText(false); // Explícitamente desactivar wrap text
        $alignment->setHorizontal('left'); // Alinear a la izquierda para mejor legibilidad

        // Ajustar manualmente el ancho de columnas si es necesario
        // ShouldAutoSize ya hace el trabajo principal, pero podemos refinar
        foreach (range('A', $lastColumn) as $columnID) {
          $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Aplicar colores condicionales por columna
        if (!empty($this->cellColorRules)) {
          // Convert color rules to normalized format if needed
          $normalizedRules = $this->normalizeColorRules($this->cellColorRules);

          $columnKeys = array_keys($this->columns);
          $columnIndices = [];
          foreach ($columnKeys as $idx => $key) {
            $columnIndices[$key] = $idx; // 0-based index
          }

          for ($rowIndex = 2; $rowIndex <= $lastRow; $rowIndex++) {
            $dataRowIndex = $rowIndex - 2;
            $rowData = $this->data->get($dataRowIndex);
            if ($rowData === null) continue;

            foreach ($normalizedRules as $columnName => $valueColorMap) {
              if (!isset($columnIndices[$columnName])) continue;

              $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndices[$columnName] + 1);
              $cellValue = is_array($rowData) ? ($rowData[$columnName] ?? null) : data_get($rowData, $columnName);

              // Check if the cell value matches any of the color rules
              if (isset($valueColorMap[$cellValue])) {
                $color = $valueColorMap[$cellValue];
                $sheet->getStyle("{$colLetter}{$rowIndex}")->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setRGB($color);
              }
            }
          }
        }
      },
    ];
  }

  /**
   * Normalize color rules from model format to internal format
   * Expected input format from model: ['column_name' => ['VALUE' => 'COLOR_HEX', ...], ...]
   * Output format: ['column_name' => ['VALUE' => 'COLOR_HEX', ...], ...]
   */
  protected function normalizeColorRules(array $colorRules): array
  {
    // If already in the expected format (column => [value => color]), return as is
    // The format from ProductWarehouseStock.getReportColorRules() is already correct:
    // ['estado_stock' => ['OUT_OF_STOCK' => 'FF0000', ...]]
    return $colorRules;
  }

  protected function resolveColor($value, array $ranges): ?string
  {
    if (!is_numeric($value)) return null;
    $value = (float)$value;

    foreach ($ranges as $range) {
      $min = $range['min'] ?? null;
      $max = $range['max'] ?? null;
      $exact = $range['exact'] ?? null;

      if ($exact !== null && $value === (float)$exact) {
        return $range['color'];
      }
      if ($min !== null && $max !== null && $value >= $min && $value < $max) {
        return $range['color'];
      }
      if ($min !== null && $max === null && $value >= $min) {
        return $range['color'];
      }
    }

    return null;
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
