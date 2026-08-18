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

    return [
      ['Periodo', $period['name']],
      ['Fecha Inicio', $period['start_date']],
      ['Fecha Fin', $period['end_date']],
      ['Días del Mes', $period['days_in_month']],
      ['Días Transcurridos', $period['days_elapsed']],
      ['Días Restantes', $period['days_remaining']],
      [],
      ['OBJETIVOS Y AVANCE', ''],
      ['Total Objetivo (S/)', number_format($summary['total_objective'], 2)],
      ['Total Avance (S/)', number_format($summary['total_progress'], 2)],
      ['% Cumplimiento', $summary['completion_percentage'] . '%'],
      ['Estado', $this->getStatusLabel($summary['status'])],
      ['Tendencia', $this->getTrendLabel($summary['trend'])],
      [],
      ['COMPARATIVA ESPERADO VS REAL', ''],
      ['% Esperado (Días transcurridos)', $summary['expected_vs_real']['expected_percentage'] . '%'],
      ['% Real', $summary['expected_vs_real']['real_percentage'] . '%'],
      ['Diferencia', $summary['expected_vs_real']['difference'] . '%'],
    ];
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
      'A8' => ['font' => ['bold' => true, 'size' => 11]],
      'A15' => ['font' => ['bold' => true, 'size' => 11]],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Auto-ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(20);

        // Aplicar estilo condicional a Estado (row 12)
        $this->applyStatusStyle($sheet, 'B12', $this->data['executive_summary']['status']);

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
