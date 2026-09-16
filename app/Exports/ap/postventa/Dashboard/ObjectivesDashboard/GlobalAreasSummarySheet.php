<?php

namespace App\Exports\ap\postventa\Dashboard\ObjectivesDashboard;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GlobalAreasSummarySheet implements
  FromCollection,
  WithHeadings,
  WithMapping,
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

  public function collection()
  {
    return collect($this->data['global_areas_summary']);
  }

  public function headings(): array
  {
    return [
      'ÁREA',
      'TIPO',
      'OBJETIVO TOTAL',
      'AVANCE TOTAL',
      'CUMPLIMIENTO (%)',
      'ESTADO',
    ];
  }

  public function map($row): array
  {
    // Determine if it's monetary or count-based
    $isVehicularCrossing = $row['is_vehicular_crossing'] ?? false;
    $objectiveLabel = $isVehicularCrossing
      ? $row['total_objective']
      : number_format($row['total_objective'], 2);
    $progressLabel = $isVehicularCrossing
      ? $row['total_progress']
      : number_format($row['total_progress'], 2);

    return [
      $row['area_name'],
      $isVehicularCrossing ? 'Unidades' : 'Soles (S/)',
      $objectiveLabel,
      $progressLabel,
      $row['completion_percentage'],
      $this->getStatusLabel($row['status']),
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'color' => ['rgb' => 'FFFFFF'],
          'size' => 11,
        ],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
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

        // Apply conditional formatting to STATUS column (F)
        for ($row = 2; $row <= $highestRow; $row++) {
          $statusCell = 'F' . $row;
          $statusValue = $sheet->getCell($statusCell)->getValue();

          $this->applyStatusStyle($sheet, $statusCell, $this->getStatusFromLabel($statusValue));

          // Apply number formatting to completion percentage column (E)
          $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.00"%"');
        }

        // Center align specific columns
        $sheet->getStyle('B2:F' . $highestRow)->getAlignment()->setHorizontal(
          \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Resumen Global por Áreas';
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

  private function getStatusFromLabel(string $label): string
  {
    return match ($label) {
      'CRÍTICO' => 'critical',
      'ALERTA' => 'warning',
      'EN META' => 'on_track',
      'SUPERADO' => 'exceeded',
      default => 'not_applicable',
    };
  }

  private function applyStatusStyle(Worksheet $sheet, string $cell, string $status): void
  {
    $colors = [
      'critical' => 'DC3545',
      'warning' => 'FFC107',
      'on_track' => '28A745',
      'exceeded' => '17A2B8',
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