<?php

namespace App\Exports\ap\postventa\Dashboard\ObjectivesDashboard;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExecutiveSummarySheet implements
  FromArray,
  WithHeadings,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected array $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function array(): array
  {
    $summary = $this->data['executive_summary'];
    $period = $this->data['period'];

    $data = [];

    // Header section
    $data[] = ['RESUMEN EJECUTIVO - DASHBOARD DE OBJETIVOS', ''];
    $data[] = ['', ''];

    // Period information
    $data[] = ['INFORMACIÓN DEL PERÍODO', ''];
    $data[] = ['Periodo', $period['name']];
    $data[] = ['Fecha Inicio', $period['start_date']];
    $data[] = ['Fecha Fin', $period['end_date']];
    $data[] = ['Fecha Actual', $period['current_date']];
    $data[] = ['Días del Mes', $period['days_in_month']];
    $data[] = ['Días Transcurridos', $period['days_elapsed']];
    $data[] = ['Días Restantes', $period['days_remaining']];
    $data[] = ['', ''];

    // Objectives and progress
    $data[] = ['OBJETIVOS Y AVANCE GLOBAL', ''];
    $data[] = ['Total Objetivo (S/)', number_format($summary['total_objective'], 2)];
    $data[] = ['Total Avance (S/)', number_format($summary['total_progress'], 2)];
    $data[] = ['Faltante (S/)', number_format($summary['total_objective'] - $summary['total_progress'], 2)];
    $data[] = ['% Cumplimiento', $summary['completion_percentage'] . '%'];
    $data[] = ['Estado', $this->getStatusLabel($summary['status'])];
    $data[] = ['Tendencia', $this->getTrendLabel($summary['trend'])];
    $data[] = ['', ''];

    // Expected vs Real comparison
    $data[] = ['COMPARATIVA ESPERADO VS REAL', ''];
    $data[] = ['% Esperado (según días transcurridos)', $summary['expected_vs_real']['expected_percentage'] . '%'];
    $data[] = ['% Real (cumplimiento actual)', $summary['expected_vs_real']['real_percentage'] . '%'];
    $data[] = ['Diferencia', $this->formatDifference($summary['expected_vs_real']['difference'])];
    $data[] = ['', ''];

    // Interpretation
    $interpretation = $this->getInterpretation($summary);
    $data[] = ['INTERPRETACIÓN', ''];
    $data[] = ['', $interpretation];

    return $data;
  }

  /**
   * Format difference with sign and color indication
   */
  private function formatDifference(float $difference): string
  {
    $sign = $difference >= 0 ? '+' : '';
    return $sign . round($difference, 2) . '%';
  }

  /**
   * Get interpretation text based on summary data
   */
  private function getInterpretation(array $summary): string
  {
    $completion = $summary['completion_percentage'];
    $expected = $summary['expected_vs_real']['expected_percentage'];
    $difference = $summary['expected_vs_real']['difference'];

    if ($difference >= 5) {
      return "El avance está por encima de lo esperado. El equipo está superando las metas proyectadas para este punto del período.";
    } elseif ($difference >= 0) {
      return "El avance está en línea con lo esperado. El ritmo de cumplimiento es adecuado según los días transcurridos.";
    } elseif ($difference >= -5) {
      return "El avance está ligeramente por debajo de lo esperado. Se recomienda monitorear de cerca y considerar acciones correctivas.";
    } else {
      return "El avance está significativamente por debajo de lo esperado. Se requieren acciones inmediatas para mejorar el ritmo de cumplimiento.";
    }
  }

  public function headings(): array
  {
    return ['Concepto', 'Valor'];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'color' => ['rgb' => 'FFFFFF'],
          'size' => 12,
        ],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
      ],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();

        // Auto-ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(80);

        // Apply header style (row 2 - RESUMEN EJECUTIVO)
        $sheet->mergeCells('A2:B2');
        $sheet->getStyle('A2')->applyFromArray([
          'font' => [
            'bold' => true,
            'size' => 14,
            'color' => ['rgb' => 'FFFFFF'],
          ],
          'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '203764'],
          ],
          'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          ],
        ]);

        // Apply section headers style
        for ($row = 1; $row <= $highestRow; $row++) {
          $cellValue = $sheet->getCell('A' . $row)->getValue();

          if (in_array($cellValue, [
            'INFORMACIÓN DEL PERÍODO',
            'OBJETIVOS Y AVANCE GLOBAL',
            'COMPARATIVA ESPERADO VS REAL',
            'INTERPRETACIÓN'
          ])) {
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
              'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
              ],
              'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
              ],
            ]);
          }

          // Apply status style to "Estado" cell
          if ($cellValue === 'Estado') {
            $statusCell = 'B' . $row;
            $this->applyStatusStyle($sheet, $statusCell, $this->data['executive_summary']['status']);
          }

          // Wrap text for interpretation
          if ($cellValue === 'INTERPRETACIÓN') {
            $interpretationRow = $row + 1;
            $sheet->getStyle('B' . $interpretationRow)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($interpretationRow)->setRowHeight(60);
          }
        }

        // Center align column B values
        $sheet->getStyle('B1:B' . $highestRow)->getAlignment()->setHorizontal(
          \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
        );

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Resumen Ejecutivo';
  }

  private function getStatusLabel(string $status): string
  {
    return match ($status) {
      'critical' => 'CRÍTICO',
      'warning' => 'ALERTA',
      'on_track' => 'EN META',
      'exceeded' => 'SUPERADO',
      default => 'N/A',
    };
  }

  private function getTrendLabel(string $trend): string
  {
    return match ($trend) {
      'up' => '↑ SUBIENDO',
      'down' => '↓ BAJANDO',
      'stable' => '→ ESTABLE',
      default => 'N/A',
    };
  }

  private function applyStatusStyle(Worksheet $sheet, string $cell, string $status): void
  {
    $colors = [
      'critical' => 'DC3545', // Rojo
      'warning' => 'FFC107',  // Amarillo
      'on_track' => '28A745', // Verde
      'exceeded' => '17A2B8', // Azul
    ];

    $color = $colors[$status] ?? 'A9A9A9';

    $sheet->getStyle($cell)->applyFromArray([
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => $color],
      ],
      'font' => [
        'color' => ['rgb' => 'FFFFFF'],
        'bold' => true,
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      ],
    ]);
  }
}
