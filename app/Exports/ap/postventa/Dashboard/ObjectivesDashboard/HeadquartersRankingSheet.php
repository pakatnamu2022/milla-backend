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
    // Extract area summaries from concepts
    $areasSummary = $this->extractAreasSummary($row['concepts_summary'] ?? []);

    return [
      $row['rank'],
      $row['name'],
      number_format($row['total_objective'], 2),
      number_format($row['total_progress'], 2),
      $row['completion_percentage'],
      $this->getStatusLabel($row['status']),
      number_format($areasSummary['taller']['objective'], 2),
      number_format($areasSummary['taller']['progress'], 2),
      $areasSummary['taller']['completion_percentage'],
      number_format($areasSummary['meson']['objective'], 2),
      number_format($areasSummary['meson']['progress'], 2),
      $areasSummary['meson']['completion_percentage'],
      $areasSummary['paso_vehicular']['objective'],
      $areasSummary['paso_vehicular']['progress'],
      $areasSummary['paso_vehicular']['completion_percentage'],
    ];
  }

  /**
   * Extract and aggregate areas summary from concepts
   */
  private function extractAreasSummary(array $concepts): array
  {
    $summary = [
      'taller' => ['objective' => 0, 'progress' => 0, 'completion_percentage' => 0],
      'meson' => ['objective' => 0, 'progress' => 0, 'completion_percentage' => 0],
      'paso_vehicular' => ['objective' => 0, 'progress' => 0, 'completion_percentage' => 0],
    ];

    foreach ($concepts as $concept) {
      $areaId = $concept['area_id'];
      $isVehicularCrossing = $concept['is_vehicular_crossing'] ?? false;

      if ($isVehicularCrossing && $areaId == \App\Models\ap\ApMasters::AREA_TALLER) {
        $summary['paso_vehicular']['objective'] += $concept['objective'];
        $summary['paso_vehicular']['progress'] += $concept['progress'];
      } elseif ($areaId == \App\Models\ap\ApMasters::AREA_TALLER) {
        $summary['taller']['objective'] += $concept['objective'];
        $summary['taller']['progress'] += $concept['progress'];
      } elseif ($areaId == \App\Models\ap\ApMasters::AREA_MESON) {
        $summary['meson']['objective'] += $concept['objective'];
        $summary['meson']['progress'] += $concept['progress'];
      }
    }

    // Calculate completion percentages
    foreach ($summary as $key => &$area) {
      $area['completion_percentage'] = $area['objective'] > 0
        ? round(($area['progress'] / $area['objective']) * 100, 2)
        : 0;
    }

    return $summary;
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
