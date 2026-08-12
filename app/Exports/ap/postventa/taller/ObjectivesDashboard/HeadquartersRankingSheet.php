<?php

namespace App\Exports\ap\postventa\taller\ObjectivesDashboard;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class HeadquartersRankingSheet implements
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
    return collect($this->data['headquarters_comparison']['ranking']);
  }

  public function headings(): array
  {
    return [
      'RANKING',
      'SEDE',
      'OBJETIVO (S/)',
      'AVANCE (S/)',
      'CUMPLIMIENTO (%)',
      'ESTADO',
      'TALLER - OBJETIVO (S/)',
      'TALLER - AVANCE (S/)',
      'TALLER - %',
      'MESÓN - OBJETIVO (S/)',
      'MESÓN - AVANCE (S/)',
      'MESÓN - %',
      'PASO VEHICULAR - OBJETIVO',
      'PASO VEHICULAR - AVANCE',
      'PASO VEHICULAR - %',
    ];
  }

  public function map($row): array
  {
    return [
      $row['rank'],
      $row['name'],
      number_format($row['total_objective'], 2),
      number_format($row['total_progress'], 2),
      $row['completion_percentage'],
      $this->getStatusLabel($row['status']),
      number_format($row['areas_summary']['workshop']['objective'], 2),
      number_format($row['areas_summary']['workshop']['progress'], 2),
      $row['areas_summary']['workshop']['completion_percentage'],
      number_format($row['areas_summary']['counter']['objective'], 2),
      number_format($row['areas_summary']['counter']['progress'], 2),
      $row['areas_summary']['counter']['completion_percentage'],
      $row['areas_summary']['vehicle_crossing']['objective'],
      $row['areas_summary']['vehicle_crossing']['progress'],
      $row['areas_summary']['vehicle_crossing']['completion_percentage'],
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

        // Habilitar filtros
        $sheet->setAutoFilter('A1:O1');

        // Aplicar estilos condicionales a columna ESTADO (F)
        for ($row = 2; $row <= $highestRow; $row++) {
          $statusCell = 'F' . $row;
          $statusValue = $sheet->getCell($statusCell)->getValue();

          $this->applyStatusStyle($sheet, $statusCell, $this->getStatusFromLabel($statusValue));
        }

        // Resaltar top 3
        for ($row = 2; $row <= min(4, $highestRow); $row++) {
          $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
          ]);
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Ranking de Sedes';
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