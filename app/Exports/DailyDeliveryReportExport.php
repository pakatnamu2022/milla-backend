<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DailyDeliveryReportExport implements WithMultipleSheets
{
  protected $reportData;

  public function __construct(array $reportData)
  {
    $this->reportData = $reportData;
  }

  public function sheets(): array
  {
    return [
      new DailyDeliveryReportSummarySheet($this->reportData),
      new DailyDeliveryReportAdvisorsSheet($this->reportData),
      new DailyDeliveryReportHierarchySheet($this->reportData),
    ];
  }
}

// Hoja 1: Resumen por Clase de Artículo
class DailyDeliveryReportSummarySheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
  protected $reportData;

  public function __construct(array $reportData)
  {
    $this->reportData = $reportData;
  }

  public function collection()
  {
    $summary = $this->reportData['summary'];
    $data = [];

    // Agregar todas las clases encontradas (excepto TOTAL)
    foreach ($summary as $className => $counts) {
      if ($className === 'TOTAL') {
        continue; // Lo agregamos al final
      }

      $data[] = [
        $className,
        $counts['entregas'],
        $counts['facturadas'],
        $counts['reporteria_dealer_portal'] ?? '',
      ];
    }

    // Agregar TOTAL al final
    if (isset($summary['TOTAL'])) {
      $data[] = [
        'TOTAL AP',
        $summary['TOTAL']['entregas'],
        $summary['TOTAL']['facturadas'],
        $summary['TOTAL']['reporteria_dealer_portal'] ?? '',
      ];
    }

    return collect($data);
  }

  public function headings(): array
  {
    return [
      ['REPORTE DIARIO DE ENTREGAS Y FACTURACIÓN'],
      ['Fecha: ' . $this->reportData['date'] . ' - Período: ' . $this->reportData['period']['month'] . '/' . $this->reportData['period']['year']],
      [],
      ['Categoría', 'Entregas', 'Facturación', 'Reportería Dealer Portal'],
    ];
  }

  public function styles(Worksheet $sheet)
  {
    $summary = $this->reportData['summary'];
    $totalRows = count($summary); // Número de clases + TOTAL
    $lastDataRow = 4 + $totalRows; // Fila donde está TOTAL

    return [
      1 => [
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E5090']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ],
      2 => [
        'font' => ['italic' => true, 'size' => 10],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ],
      4 => [
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
      ],
      $lastDataRow => [ // TOTAL AP row (última fila dinámica)
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
      ],
    ];
  }

  public function title(): string
  {
    return 'Resumen';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $summary = $this->reportData['summary'];
        $totalRows = count($summary);
        $lastDataRow = 4 + $totalRows;

        // Merge cells for title
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(25);

        // Add borders (dinámico según cantidad de clases)
        $sheet->getStyle('A4:D' . $lastDataRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        // Center align numbers (dinámico)
        $sheet->getStyle('B5:D' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
      },
    ];
  }
}

// Hoja 2: Desglose por Asesores
class DailyDeliveryReportAdvisorsSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
  protected $reportData;

  public function __construct(array $reportData)
  {
    $this->reportData = $reportData;
  }

  public function collection()
  {
    $advisors = $this->reportData['advisors'];

    $data = [];
    foreach ($advisors as $advisor) {
      $data[] = [
        $advisor['name'],
        $advisor['entregas'],
        $advisor['facturadas'],
        $advisor['reporteria_dealer_portal'] ?? '',
      ];
    }

    return collect($data);
  }

  public function headings(): array
  {
    return [
      ['DESGLOSE POR ASESORES'],
      ['Fecha: ' . $this->reportData['date']],
      [],
      ['Asesor', 'Entregas', 'Facturación', 'Reportería Dealer Portal'],
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E5090']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ],
      2 => [
        'font' => ['italic' => true, 'size' => 10],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ],
      4 => [
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
      ],
    ];
  }

  public function title(): string
  {
    return 'Asesores';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Merge cells for title
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(25);

        $lastRow = $sheet->getHighestRow();

        // Add borders
        $sheet->getStyle('A4:D' . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        // Center align numbers
        $sheet->getStyle('B5:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Freeze panes
        $sheet->freezePane('A5');

        // Auto filter
        $sheet->setAutoFilter('A4:D4');
      },
    ];
  }
}

// Hoja 3: Jerarquía
class DailyDeliveryReportHierarchySheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
  protected $reportData;
  protected $flattenedData = [];

  public function __construct(array $reportData)
  {
    $this->reportData = $reportData;
    $this->flattenHierarchy($reportData['hierarchy']);
  }

  protected function flattenHierarchy(array $nodes, int $level = 0)
  {
    foreach ($nodes as $node) {
      $indent = str_repeat('  ', $level);
      $levelLabel = '';

      switch ($node['level']) {
        case 'gerente':
          $levelLabel = '📊 ';
          break;
        case 'jefe':
          $levelLabel = '👔 ';
          break;
        case 'asesor':
          $levelLabel = '👤 ';
          break;
      }

      $this->flattenedData[] = [
        'name' => $indent . $levelLabel . $node['name'],
        'level' => ucfirst($node['level']),
        'entregas' => $node['entregas'],
        'facturadas' => $node['facturadas'],
        'reporteria' => $node['reporteria_dealer_portal'] ?? '',
      ];

      if (!empty($node['children'])) {
        $this->flattenHierarchy($node['children'], $level + 1);
      }
    }
  }

  public function collection()
  {
    return collect($this->flattenedData);
  }

  public function headings(): array
  {
    return [
      ['JERARQUÍA ORGANIZACIONAL - GERENTE > JEFE > ASESOR'],
      ['Fecha: ' . $this->reportData['date']],
      [],
      ['Nombre', 'Nivel', 'Entregas', 'Facturación', 'Reportería Dealer Portal'],
    ];
  }

  public function styles(Worksheet $sheet)
  {
    $lastRow = count($this->flattenedData) + 4;

    $styles = [
      1 => [
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E5090']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ],
      2 => [
        'font' => ['italic' => true, 'size' => 10],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ],
      4 => [
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
      ],
    ];

    // Apply styles for gerentes, jefes, asesores
    for ($row = 5; $row <= $lastRow; $row++) {
      $level = $this->flattenedData[$row - 5]['level'] ?? '';

      if ($level === 'Gerente') {
        $styles[$row] = [
          'font' => ['bold' => true, 'size' => 11],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
        ];
      } elseif ($level === 'Jefe') {
        $styles[$row] = [
          'font' => ['bold' => true],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECF5']],
        ];
      }
    }

    return $styles;
  }

  public function title(): string
  {
    return 'Jerarquía';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Merge cells for title
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(25);

        $lastRow = $sheet->getHighestRow();

        // Add borders
        $sheet->getStyle('A4:E' . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        // Center align numbers and level
        $sheet->getStyle('B5:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Freeze panes
        $sheet->freezePane('A5');

        // Auto filter
        $sheet->setAutoFilter('A4:E4');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(50);
      },
    ];
  }
}
