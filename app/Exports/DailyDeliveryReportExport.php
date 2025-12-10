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
use App\Exports\AvancePorSedeSheet;

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
      new DailyDeliveryReportBrandsSheet($this->reportData),
      new AvancePorSedeSheet($this->reportData),
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
      ['Período: ' . $this->reportData['fecha_inicio'] . ' al ' . $this->reportData['fecha_fin']],
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
      ['Período: ' . $this->reportData['fecha_inicio'] . ' al ' . $this->reportData['fecha_fin']],
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

      $this->flattenedData[] = [
        'name' => $indent . $node['name'],
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
      ['Período: ' . $this->reportData['fecha_inicio'] . ' al ' . $this->reportData['fecha_fin']],
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

        // Agrupar filas en 3 niveles: Gerente > Jefe > Asesor
        $currentGerenteRow = null;
        $jefeStartRow = null;
        $currentJefeRow = null;
        $asesorStartRow = null;

        for ($row = 5; $row <= $lastRow; $row++) {
          $level = $this->flattenedData[$row - 5]['level'] ?? '';

          if ($level === 'Gerente') {
            // Cerrar grupo de asesores del jefe anterior
            if ($currentJefeRow !== null && $asesorStartRow !== null) {
              for ($i = $asesorStartRow; $i <= $row - 1; $i++) {
                $sheet->getRowDimension($i)->setOutlineLevel(2);
              }
            }

            // Cerrar grupo de jefes del gerente anterior
            if ($currentGerenteRow !== null && $jefeStartRow !== null) {
              for ($i = $jefeStartRow; $i <= $row - 1; $i++) {
                if ($sheet->getRowDimension($i)->getOutlineLevel() === 0) {
                  $sheet->getRowDimension($i)->setOutlineLevel(1);
                }
              }
            }

            // Nuevo gerente
            $currentGerenteRow = $row;
            $jefeStartRow = null;
            $currentJefeRow = null;
            $asesorStartRow = null;
          } elseif ($level === 'Jefe') {
            // Cerrar grupo de asesores del jefe anterior
            if ($currentJefeRow !== null && $asesorStartRow !== null) {
              for ($i = $asesorStartRow; $i <= $row - 1; $i++) {
                $sheet->getRowDimension($i)->setOutlineLevel(2);
              }
            }

            // Marcar inicio de jefes si es el primero
            if ($jefeStartRow === null) {
              $jefeStartRow = $row;
            }

            // Nuevo jefe
            $currentJefeRow = $row;
            $asesorStartRow = null;
          } elseif ($level === 'Asesor') {
            if ($asesorStartRow === null) {
              $asesorStartRow = $row;
            }
          }
        }

        // Cerrar últimos grupos
        if ($currentJefeRow !== null && $asesorStartRow !== null) {
          for ($i = $asesorStartRow; $i <= $lastRow; $i++) {
            $sheet->getRowDimension($i)->setOutlineLevel(2);
          }
        }

        if ($currentGerenteRow !== null && $jefeStartRow !== null) {
          for ($i = $jefeStartRow; $i <= $lastRow; $i++) {
            if ($sheet->getRowDimension($i)->getOutlineLevel() === 0) {
              $sheet->getRowDimension($i)->setOutlineLevel(1);
            }
          }
        }

        // Colapsar grupos por defecto
        $sheet->setShowSummaryBelow(false);
      },
    ];
  }
}

// Hoja 4: Reporte por Marcas
class DailyDeliveryReportBrandsSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
  protected $reportData;
  protected $flattenedData = [];

  public function __construct(array $reportData)
  {
    $this->reportData = $reportData;
    $this->flattenBrandReport();
  }

  protected function flattenBrandReport()
  {
    if (!isset($this->reportData['brand_report'])) {
      return;
    }

    foreach ($this->reportData['brand_report'] as $section) {
      // Agregar título del grupo como encabezado con totales
      $this->flattenedData[] = [
        'name' => $section['title'],
        'level' => 'title',
        'compras' => $section['total_compras'] ?? '',
        'entregas' => $section['total_entregas'] ?? '',
        'facturadas' => $section['total_facturadas'] ?? '',
        'reporteria' => '',
      ];

      // Agregar items del grupo (sedes y marcas, sin el item "AP Total")
      foreach ($section['items'] as $item) {
        $indent = '';

        switch ($item['level']) {
          case 'sede':
            $indent = '  ';
            break;
          case 'brand':
            $indent = '    ';
            break;
        }

        $this->flattenedData[] = [
          'name' => $indent . $item['name'],
          'level' => $item['level'],
          'compras' => $item['compras'],
          'entregas' => $item['entregas'],
          'facturadas' => $item['facturadas'],
          'reporteria' => $item['reporteria_dealer_portal'] ?? '',
        ];
      }

      // Agregar fila vacía entre secciones
      $this->flattenedData[] = [
        'name' => '',
        'level' => 'separator',
        'compras' => '',
        'entregas' => '',
        'facturadas' => '',
        'reporteria' => '',
      ];
    }
  }

  public function collection()
  {
    // Convertir array asociativo a array de valores en orden: Descripción, Compras, Entregas, Facturación, Reportería
    return collect($this->flattenedData)->map(function ($row) {
      return [
        $row['name'],
        $row['compras'],
        $row['entregas'],
        $row['facturadas'],
        $row['reporteria'],
      ];
    });
  }

  public function headings(): array
  {
    return [
      ['REPORTE POR MARCAS Y SEDES'],
      ['Período: ' . $this->reportData['fecha_inicio'] . ' al ' . $this->reportData['fecha_fin']],
      [],
      ['Descripción', 'Compras', 'Entregas', 'Facturación', 'Reportería Dealer Portal'],
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

    // Apply styles for different levels
    for ($row = 5; $row <= $lastRow; $row++) {
      $level = $this->flattenedData[$row - 5]['level'] ?? '';

      if ($level === 'title') {
        $styles[$row] = [
          'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E5090']],
        ];
      } elseif ($level === 'group') {
        $styles[$row] = [
          'font' => ['bold' => true, 'size' => 11],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
        ];
      } elseif ($level === 'sede') {
        $styles[$row] = [
          'font' => ['bold' => false],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECF5']],
        ];
      }
    }

    return $styles;
  }

  public function title(): string
  {
    return 'Reporte por Marcas';
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

        // Center align numbers
        $sheet->getStyle('B5:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Freeze panes
        $sheet->freezePane('A5');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(50);

        // Agrupar filas por sede (agrupar marcas bajo su sede)
        $currentSedeRow = null;
        $brandStartRow = null;

        for ($row = 5; $row <= $lastRow; $row++) {
          $level = $this->flattenedData[$row - 5]['level'] ?? '';

          if ($level === 'sede') {
            // Si ya había una sede anterior, agrupar sus marcas
            if ($currentSedeRow !== null && $brandStartRow !== null) {
              $groupEndRow = $row - 1;
              if ($groupEndRow >= $brandStartRow) {
                for ($i = $brandStartRow; $i <= $groupEndRow; $i++) {
                  $sheet->getRowDimension($i)->setOutlineLevel(1);
                }
              }
            }

            // Nueva sede
            $currentSedeRow = $row;
            $brandStartRow = null;
          } elseif ($level === 'brand') {
            if ($brandStartRow === null) {
              $brandStartRow = $row;
            }
          } elseif ($level === 'separator' || $level === 'title') {
            // Si encontramos un separador o título, cerrar grupo anterior
            if ($currentSedeRow !== null && $brandStartRow !== null) {
              $groupEndRow = $row - 1;
              if ($groupEndRow >= $brandStartRow) {
                for ($i = $brandStartRow; $i <= $groupEndRow; $i++) {
                  $sheet->getRowDimension($i)->setOutlineLevel(1);
                }
              }
            }
            $currentSedeRow = null;
            $brandStartRow = null;
          }
        }

        // Agrupar las últimas marcas si existen
        if ($currentSedeRow !== null && $brandStartRow !== null) {
          $groupEndRow = $lastRow;
          if ($groupEndRow >= $brandStartRow) {
            for ($i = $brandStartRow; $i <= $groupEndRow; $i++) {
              $sheet->getRowDimension($i)->setOutlineLevel(1);
            }
          }
        }

        // Colapsar grupos por defecto
        $sheet->setShowSummaryBelow(false);
      },
    ];
  }
}
