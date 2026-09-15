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
    $data[] = ['Sede', $this->headquarter['name'], '', ''];
    $data[] = ['Objetivo Total (S/)', number_format($this->headquarter['total_objective'], 2), '', ''];
    $data[] = ['Avance Total (S/)', number_format($this->headquarter['total_progress'], 2), '', ''];
    $data[] = ['% Cumplimiento', $this->headquarter['completion_percentage'] . '%', '', ''];
    $data[] = ['Estado', $this->getStatusLabel($this->headquarter['status']), '', ''];
    $data[] = ['', '', '', ''];

    // Group concepts by area
    $conceptsByArea = $this->groupConceptsByArea($this->headquarter['concepts'] ?? []);

    // TALLER
    if (!empty($conceptsByArea['taller'])) {
      $data = array_merge($data, $this->buildTallerSection($conceptsByArea['taller']));
    }

    // MESÓN
    if (!empty($conceptsByArea['meson'])) {
      $data = array_merge($data, $this->buildMesonSection($conceptsByArea['meson']));
    }

    // PASO VEHICULAR
    if (!empty($conceptsByArea['paso_vehicular'])) {
      $data = array_merge($data, $this->buildPasoVehicularSection($conceptsByArea['paso_vehicular']));
    }

    // OTROS CONCEPTOS (if any)
    if (!empty($conceptsByArea['otros'])) {
      $data = array_merge($data, $this->buildOtrosSection($conceptsByArea['otros']));
    }

    return $data;
  }

  /**
   * Group concepts by area type
   */
  private function groupConceptsByArea(array $concepts): array
  {
    $grouped = [
      'taller' => [],
      'meson' => [],
      'paso_vehicular' => [],
      'otros' => []
    ];

    foreach ($concepts as $concept) {
      $areaId = $concept['area_id'];
      $isVehicularCrossing = $concept['is_vehicular_crossing'] ?? false;

      if ($isVehicularCrossing && $areaId == \App\Models\ap\ApMasters::AREA_TALLER) {
        $grouped['paso_vehicular'][] = $concept;
      } elseif ($areaId == \App\Models\ap\ApMasters::AREA_TALLER) {
        $grouped['taller'][] = $concept;
      } elseif ($areaId == \App\Models\ap\ApMasters::AREA_MESON) {
        $grouped['meson'][] = $concept;
      } else {
        $grouped['otros'][] = $concept;
      }
    }

    return $grouped;
  }

  /**
   * Build Taller section
   */
  private function buildTallerSection(array $tallerConcepts): array
  {
    $data = [];
    $data[] = ['ÁREA: TALLER', '', '', ''];

    // Aggregate all taller concepts
    $totalObjective = 0;
    $totalProgress = 0;
    $allBrands = [];
    $allAdvisors = [];

    foreach ($tallerConcepts as $concept) {
      $totalObjective += $concept['objective'];
      $totalProgress += $concept['progress'];

      // Merge brands
      if (!empty($concept['by_brand'])) {
        foreach ($concept['by_brand'] as $brand) {
          $brandName = $brand['brand_name'];
          if (!isset($allBrands[$brandName])) {
            $allBrands[$brandName] = [
              'brand_name' => $brandName,
              'total_billing' => 0,
              'vehicle_count' => 0
            ];
          }
          $allBrands[$brandName]['total_billing'] += $brand['total_billing'];
          $allBrands[$brandName]['vehicle_count'] += $brand['vehicle_count'];
        }
      }

      // Merge advisors
      if (!empty($concept['top_advisors'])) {
        foreach ($concept['top_advisors'] as $advisor) {
          $advisorId = $advisor['advisor_id'];
          if (!isset($allAdvisors[$advisorId])) {
            $allAdvisors[$advisorId] = $advisor;
          } else {
            $allAdvisors[$advisorId]['objective'] += $advisor['objective'];
            $allAdvisors[$advisorId]['progress'] += $advisor['progress'];
          }
        }
      }
    }

    $completionPercentage = $totalObjective > 0 ? round(($totalProgress / $totalObjective) * 100, 2) : 0;

    $data[] = ['Objetivo (S/)', number_format($totalObjective, 2), '', ''];
    $data[] = ['Avance (S/)', number_format($totalProgress, 2), '', ''];
    $data[] = ['% Cumplimiento', $completionPercentage . '%', '', ''];
    $data[] = ['', '', '', ''];

    // Individual concepts detail
    if (count($tallerConcepts) > 1) {
      $data[] = ['DETALLE POR CONCEPTO', '', '', ''];
      $data[] = ['Concepto', 'Objetivo (S/)', 'Avance (S/)', '% Cumplimiento'];
      foreach ($tallerConcepts as $concept) {
        $data[] = [
          $concept['description'],
          number_format($concept['objective'], 2),
          number_format($concept['progress'], 2),
          $concept['completion_percentage'] . '%',
        ];
      }
      $data[] = ['', '', '', ''];
    }

    // Facturación por Marca
    if (!empty($allBrands)) {
      $totalBilling = array_sum(array_column($allBrands, 'total_billing'));

      // Calculate percentages
      foreach ($allBrands as &$brand) {
        $brand['percentage_of_total'] = $totalBilling > 0
          ? round(($brand['total_billing'] / $totalBilling) * 100, 2)
          : 0;
      }

      // Sort by billing desc
      usort($allBrands, fn($a, $b) => $b['total_billing'] <=> $a['total_billing']);

      $data[] = ['FACTURACIÓN POR MARCA - TALLER', '', '', ''];
      $data[] = ['Marca', 'Facturación (S/)', 'Cantidad Vehículos', '% del Total'];

      foreach ($allBrands as $brand) {
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
    if (!empty($allAdvisors)) {
      // Recalculate completion percentages
      $allAdvisors = collect($allAdvisors)->map(function ($advisor) {
        $advisor['completion_percentage'] = $advisor['objective'] > 0
          ? round(($advisor['progress'] / $advisor['objective']) * 100, 2)
          : 0;
        return $advisor;
      })->sortByDesc('completion_percentage')->take(10)->values()->toArray();

      // Add ranking
      $rank = 1;
      foreach ($allAdvisors as &$advisor) {
        $advisor['rank'] = $rank++;
      }

      $data[] = ['TOP 10 ASESORES - TALLER', '', '', ''];
      $data[] = ['Ranking', 'Asesor', 'Objetivo (S/)', 'Avance (S/)', '% Cumplimiento', 'Estado'];

      foreach ($allAdvisors as $advisor) {
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

    $data[] = ['', '', '', ''];
    return $data;
  }

  /**
   * Build Mesón section
   */
  private function buildMesonSection(array $mesonConcepts): array
  {
    $data = [];
    $data[] = ['ÁREA: MESÓN / REPUESTOS', '', '', ''];

    // Aggregate all mesón concepts
    $totalObjective = 0;
    $totalProgress = 0;

    foreach ($mesonConcepts as $concept) {
      $totalObjective += $concept['objective'];
      $totalProgress += $concept['progress'];
    }

    $completionPercentage = $totalObjective > 0 ? round(($totalProgress / $totalObjective) * 100, 2) : 0;

    $data[] = ['Objetivo (S/)', number_format($totalObjective, 2), '', ''];
    $data[] = ['Avance (S/)', number_format($totalProgress, 2), '', ''];
    $data[] = ['% Cumplimiento', $completionPercentage . '%', '', ''];
    $data[] = ['', '', '', ''];

    // Individual concepts detail
    if (count($mesonConcepts) > 1) {
      $data[] = ['DETALLE POR CONCEPTO', '', '', ''];
      $data[] = ['Concepto', 'Objetivo (S/)', 'Avance (S/)', '% Cumplimiento'];
      foreach ($mesonConcepts as $concept) {
        $data[] = [
          $concept['description'],
          number_format($concept['objective'], 2),
          number_format($concept['progress'], 2),
          $concept['completion_percentage'] . '%',
        ];
      }
      $data[] = ['', '', '', ''];
    }

    $data[] = ['', '', '', ''];
    return $data;
  }

  /**
   * Build Paso Vehicular section
   */
  private function buildPasoVehicularSection(array $pasoVehicularConcepts): array
  {
    $data = [];
    $data[] = ['PASO VEHICULAR', '', '', ''];

    // Aggregate all paso vehicular concepts
    $totalObjective = 0;
    $totalProgress = 0;
    $allBrands = [];

    foreach ($pasoVehicularConcepts as $concept) {
      $totalObjective += $concept['objective'];
      $totalProgress += $concept['progress'];

      // Merge brands
      if (!empty($concept['by_brand'])) {
        foreach ($concept['by_brand'] as $brand) {
          $brandName = $brand['brand_name'];
          if (!isset($allBrands[$brandName])) {
            $allBrands[$brandName] = [
              'brand_name' => $brandName,
              'count' => 0
            ];
          }
          $allBrands[$brandName]['count'] += $brand['count'];
        }
      }
    }

    $completionPercentage = $totalObjective > 0 ? round(($totalProgress / $totalObjective) * 100, 2) : 0;

    $data[] = ['Objetivo (unidades)', $totalObjective, '', ''];
    $data[] = ['Avance (unidades)', $totalProgress, '', ''];
    $data[] = ['% Cumplimiento', $completionPercentage . '%', '', ''];
    $data[] = ['', '', '', ''];

    // Individual concepts detail
    if (count($pasoVehicularConcepts) > 1) {
      $data[] = ['DETALLE POR CONCEPTO', '', '', ''];
      $data[] = ['Concepto', 'Objetivo', 'Avance', '% Cumplimiento'];
      foreach ($pasoVehicularConcepts as $concept) {
        $data[] = [
          $concept['description'],
          $concept['objective'],
          $concept['progress'],
          $concept['completion_percentage'] . '%',
        ];
      }
      $data[] = ['', '', '', ''];
    }

    // Paso Vehicular por Marca
    if (!empty($allBrands)) {
      $totalCount = array_sum(array_column($allBrands, 'count'));

      // Calculate percentages
      foreach ($allBrands as &$brand) {
        $brand['percentage_of_total'] = $totalCount > 0
          ? round(($brand['count'] / $totalCount) * 100, 2)
          : 0;
      }

      // Sort by count desc
      usort($allBrands, fn($a, $b) => $b['count'] <=> $a['count']);

      $data[] = ['PASO VEHICULAR POR MARCA', '', '', ''];
      $data[] = ['Marca', 'Cantidad', '% del Total', ''];

      foreach ($allBrands as $brand) {
        $data[] = [
          $brand['brand_name'],
          $brand['count'],
          $brand['percentage_of_total'] . '%',
          '',
        ];
      }
      $data[] = ['', '', '', ''];
    }

    $data[] = ['', '', '', ''];
    return $data;
  }

  /**
   * Build Otros conceptos section
   */
  private function buildOtrosSection(array $otrosConcepts): array
  {
    $data = [];
    $data[] = ['OTROS CONCEPTOS', '', '', ''];

    foreach ($otrosConcepts as $concept) {
      $data[] = [$concept['description'], '', '', ''];
      $data[] = ['Área', $concept['area_name'], '', ''];
      $data[] = ['Objetivo', number_format($concept['objective'], 2), '', ''];
      $data[] = ['Avance', number_format($concept['progress'], 2), '', ''];
      $data[] = ['% Cumplimiento', $concept['completion_percentage'] . '%', '', ''];
      $data[] = ['Estado', $this->getStatusLabel($concept['status']), '', ''];
      $data[] = ['', '', '', ''];
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
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(15);

        // Aplicar estilo a títulos de secciones
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
          $cellValue = $sheet->getCell('A' . $row)->getValue();

          // Secciones principales
          if (in_array($cellValue, [
            'RESUMEN GENERAL',
            'ÁREA: TALLER',
            'ÁREA: MESÓN / REPUESTOS',
            'PASO VEHICULAR',
            'OTROS CONCEPTOS'
          ])) {
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
          if (str_contains($cellValue, 'FACTURACIÓN POR MARCA') ||
            str_contains($cellValue, 'TOP') ||
            str_contains($cellValue, 'PASO VEHICULAR POR MARCA') ||
            str_contains($cellValue, 'DETALLE POR CONCEPTO')) {
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

          // Apply header row style for tables
          if (in_array($cellValue, ['Marca', 'Ranking', 'Concepto'])) {
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
              'font' => ['bold' => true],
              'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'],
              ],
            ]);
          }

          // Apply status colors to Estado cells
          if ($cellValue === 'Estado') {
            $statusValue = $sheet->getCell('B' . $row)->getValue();
            $this->applyStatusStyle($sheet, 'B' . $row, $this->getStatusFromLabel($statusValue));
          }
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  /**
   * Get status from label
   */
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

  /**
   * Apply status color style
   */
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
