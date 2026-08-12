<?php

namespace App\Exports\ap\postventa\taller\ObjectivesDashboard;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HeadquarterDetailSheet implements
  FromArray,
  WithHeadings,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected array $headquarter;

  public function __construct(array $headquarter)
  {
    $this->headquarter = $headquarter;
  }

  public function array(): array
  {
    $data = [];

    // RESUMEN GENERAL
    $data[] = ['RESUMEN GENERAL', '', '', ''];
    $data[] = ['Objetivo Total (S/)', number_format($this->headquarter['total_objective'], 2), '', ''];
    $data[] = ['Avance Total (S/)', number_format($this->headquarter['total_progress'], 2), '', ''];
    $data[] = ['% Cumplimiento', $this->headquarter['completion_percentage'] . '%', '', ''];
    $data[] = ['Estado', $this->getStatusLabel($this->headquarter['status']), '', ''];
    $data[] = ['', '', '', ''];

    // TALLER
    $workshop = $this->headquarter['workshop'];
    $data[] = ['ÁREA: TALLER', '', '', ''];
    $data[] = ['Objetivo (S/)', number_format($workshop['objective'], 2), '', ''];
    $data[] = ['Avance (S/)', number_format($workshop['progress'], 2), '', ''];
    $data[] = ['% Cumplimiento', $workshop['completion_percentage'] . '%', '', ''];
    $data[] = ['', '', '', ''];

    // Facturación por Marca (Taller)
    if (!empty($workshop['by_brand'])) {
      $data[] = ['FACTURACIÓN POR MARCA - TALLER', '', '', ''];
      $data[] = ['Marca', 'Facturación (S/)', 'Cantidad Vehículos', '% del Total'];

      foreach ($workshop['by_brand'] as $brand) {
        $data[] = [
          $brand['brand_name'],
          number_format($brand['total_billing'], 2),
          $brand['vehicle_count'],
          $brand['percentage_of_total'] . '%',
        ];
      }
      $data[] = ['', '', '', ''];
    }

    // Top Asesores
    if (!empty($workshop['top_advisors'])) {
      $data[] = ['TOP ASESORES - TALLER', '', '', ''];
      $data[] = ['Ranking', 'Asesor', 'Objetivo (S/)', 'Avance (S/)', '% Cumplimiento', 'Estado'];

      foreach ($workshop['top_advisors'] as $advisor) {
        $data[] = [
          $advisor['rank'],
          $advisor['advisor_name'],
          number_format($advisor['objective'], 2),
          number_format($advisor['progress'], 2),
          $advisor['completion_percentage'] . '%',
          $this->getStatusLabel($advisor['status']),
        ];
      }
      $data[] = ['', '', '', '', '', ''];
    }

    // MESÓN
    $counter = $this->headquarter['counter'];
    $data[] = ['ÁREA: MESÓN', '', '', ''];
    $data[] = ['Objetivo (S/)', number_format($counter['objective'], 2), '', ''];
    $data[] = ['Avance (S/)', number_format($counter['progress'], 2), '', ''];
    $data[] = ['% Cumplimiento', $counter['completion_percentage'] . '%', '', ''];
    $data[] = ['', '', '', ''];

    // PASO VEHICULAR
    $vehicleCrossing = $this->headquarter['vehicle_crossing'];
    $data[] = ['PASO VEHICULAR', '', '', ''];
    $data[] = ['Objetivo', $vehicleCrossing['objective'], '', ''];
    $data[] = ['Avance', $vehicleCrossing['progress'], '', ''];
    $data[] = ['% Cumplimiento', $vehicleCrossing['completion_percentage'] . '%', '', ''];
    $data[] = ['', '', '', ''];

    // Paso Vehicular por Marca
    if (!empty($vehicleCrossing['by_brand'])) {
      $data[] = ['PASO VEHICULAR POR MARCA', '', '', ''];
      $data[] = ['Marca', 'Cantidad', '% del Total', ''];

      foreach ($vehicleCrossing['by_brand'] as $brand) {
        $data[] = [
          $brand['brand_name'],
          $brand['count'],
          $brand['percentage_of_total'] . '%',
          '',
        ];
      }
    }

    return $data;
  }

  public function headings(): array
  {
    return [];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      'A' => ['font' => ['bold' => true]],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Auto-ajustar columnas
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);

        // Aplicar estilo a títulos de secciones
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
          $cellValue = $sheet->getCell('A' . $row)->getValue();

          // Secciones principales
          if (in_array($cellValue, ['RESUMEN GENERAL', 'ÁREA: TALLER', 'ÁREA: MESÓN', 'PASO VEHICULAR'])) {
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
              'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
              ],
              'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
              ],
            ]);
          }

          // Sub-secciones
          if (str_contains($cellValue, 'FACTURACIÓN POR MARCA') || str_contains($cellValue, 'TOP ASESORES') || str_contains($cellValue, 'PASO VEHICULAR POR MARCA')) {
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
              'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
              ],
              'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'],
              ],
            ]);
          }
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    // Limit to 31 characters (Excel sheet name limit)
    $name = substr($this->headquarter['abbreviation'], 0, 31);
    return $name ?: 'Detalle';
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
}