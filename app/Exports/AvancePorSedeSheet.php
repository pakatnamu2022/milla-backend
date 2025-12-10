<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Hoja 5: Avance por Sede
class AvancePorSedeSheet implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle, WithEvents
{
  protected $reportData;
  protected $flattenedData = [];

  public function __construct(array $reportData)
  {
    $this->reportData = $reportData;
    $this->flattenAvancePorSede();
  }

  protected function flattenAvancePorSede()
  {
    if (!isset($this->reportData['avance_por_sede'])) {
      return;
    }

    foreach ($this->reportData['avance_por_sede'] as $sedeNode) {
      // Agregar encabezado de sede
      $this->flattenedData[] = [
        'descripcion' => $sedeNode['sede_name'],
        'level' => 'sede',

        // Sección 1: Entregas (Sell Out)
        'objetivo_ap_entregas' => '',
        'resultado_entrega' => '',
        'cumplimiento_entrega' => '',

        // Sección 2: Reportes
        'objetivos_reporte_inchcape' => '',
        'reporte_dealer_portal' => '',
        'cumplimiento_reporte' => '',

        // Sección 3: Compras (Sell In)
        'objetivos_compra_inchcape' => '',
        'avance_compra' => '',
        'cumplimiento_compra' => '',
      ];

      // Agregar marcas de esta sede
      foreach ($sedeNode['brands'] as $brand) {
        $this->flattenedData[] = [
          'descripcion' => '  ' . $brand['brand_name'],
          'level' => 'brand',

          // Sección 1: Entregas (Sell Out)
          'objetivo_ap_entregas' => $brand['objetivo_ap_entregas'],
          'resultado_entrega' => $brand['resultado_entrega'],
          'cumplimiento_entrega' => $brand['cumplimiento_entrega'] !== null ? $brand['cumplimiento_entrega'] . '%' : '',

          // Sección 2: Reportes
          'objetivos_reporte_inchcape' => $brand['objetivos_reporte_inchcape'],
          'reporte_dealer_portal' => $brand['reporte_dealer_portal'] ?? '',
          'cumplimiento_reporte' => $brand['cumplimiento_reporte'] !== null ? $brand['cumplimiento_reporte'] . '%' : '',

          // Sección 3: Compras (Sell In)
          'objetivos_compra_inchcape' => $brand['objetivos_compra_inchcape'],
          'avance_compra' => $brand['avance_compra'],
          'cumplimiento_compra' => $brand['cumplimiento_compra'] !== null ? $brand['cumplimiento_compra'] . '%' : '',
        ];
      }
    }
  }

  public function collection()
  {
    return collect($this->flattenedData)->map(function ($row) {
      return [
        $row['descripcion'],
        $row['objetivo_ap_entregas'],
        $row['resultado_entrega'],
        $row['cumplimiento_entrega'],
        $row['objetivos_reporte_inchcape'],
        $row['reporte_dealer_portal'],
        $row['cumplimiento_reporte'],
        $row['objetivos_compra_inchcape'],
        $row['avance_compra'],
        $row['cumplimiento_compra'],
      ];
    });
  }

  public function headings(): array
  {
    return [
      ['AVANCE POR SEDE'],
      ['Período: ' . $this->reportData['fecha_inicio'] . ' al ' . $this->reportData['fecha_fin']],
      [],
      [
        'Descripción',
        'Objetivo AP Entregas',
        'Resultado Entrega',
        'Cumplimiento (%)',
        'Objetivos Reporte Inchcape',
        'Reporte Dealer Portal',
        'Cumplimiento (%)',
        'Objetivos Compra Inchcape',
        'Avance de Compra',
        'Cumplimiento (%)',
      ],
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
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => [
          'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'FFFFFF'],
          ],
        ],
      ],
    ];

    // Aplicar estilos por nivel
    for ($row = 5; $row <= $lastRow; $row++) {
      $level = $this->flattenedData[$row - 5]['level'] ?? '';

      if ($level === 'sede') {
        $styles[$row] = [
          'font' => ['bold' => true, 'size' => 11],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
        ];
      }
    }

    return $styles;
  }

  public function title(): string
  {
    return 'Avance por Sede';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Merge cells for title
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(35);

        $lastRow = $sheet->getHighestRow();

        // Add borders
        $sheet->getStyle('A4:J' . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        // Center align numbers
        $sheet->getStyle('B5:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Freeze panes
        $sheet->freezePane('A5');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(40);

        // Merge cells para separar secciones en el encabezado (fila 4)
        // Sección 1: Entregas (columnas B, C, D)
        // Sección 2: Reportes (columnas E, F, G)
        // Sección 3: Compras (columnas H, I, J)

        // Agregar una fila adicional para los títulos de las secciones
        $sheet->insertNewRowBefore(4, 1);

        // Títulos de secciones
        $sheet->setCellValue('B4', 'ENTREGAS (SELL OUT)');
        $sheet->setCellValue('E4', 'REPORTES');
        $sheet->setCellValue('H4', 'COMPRAS (SELL IN)');

        $sheet->mergeCells('B4:D4');
        $sheet->mergeCells('E4:G4');
        $sheet->mergeCells('H4:J4');

        // Estilo para títulos de secciones
        $sheet->getStyle('B4:J4')->applyFromArray([
          'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color' => ['rgb' => 'FFFFFF'],
            ],
          ],
        ]);

        // Ajustar merge de título principal
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        // Agrupar marcas bajo sus sedes
        $currentSedeRow = null;
        $brandStartRow = null;

        for ($row = 6; $row <= $lastRow + 1; $row++) {
          $dataIndex = $row - 6;
          if (!isset($this->flattenedData[$dataIndex])) {
            break;
          }

          $level = $this->flattenedData[$dataIndex]['level'] ?? '';

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
          }
        }

        // Agrupar las últimas marcas si existen
        if ($currentSedeRow !== null && $brandStartRow !== null) {
          $groupEndRow = $lastRow + 1;
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
