<?php

namespace App\Exports\ap\postventa\taller;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WorkedHoursSummarySheet implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $data;

  public function __construct(Collection $data)
  {
    $this->data = $data;
  }

  public function collection()
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'SEDE',
      'DNI TÉCNICO',
      'NOMBRE TÉCNICO',
      'HORAS INTERNA',
      'HORAS ESTÁNDAR',
      'HORAS GARANTÍA/RECALL',
      'TOTAL HORAS',
    ];
  }

  public function map($row): array
  {
    return [
      $row['sede'],
      $row['dni_tecnico'],
      $row['nombre_tecnico'],
      $row['horas_interna'],
      $row['horas_estandar'],
      $row['horas_garantia_recall'],
      $row['total_horas'],
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      // Estilo del encabezado
      1 => [
        'font' => [
          'bold' => true,
          'color' => ['rgb' => 'FFFFFF'],
          'size' => 11,
        ],
        'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER,
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

        // Habilitar filtros en la fila de encabezado
        $sheet->setAutoFilter('A1:G1');

        // Aplicar estilo de negrita y color gris a la fila de totales (última fila)
        if ($highestRow > 1) {
          $totalRowStyle = [
            'font' => [
              'bold' => true,
              'size' => 11,
            ],
            'fill' => [
              'fillType' => Fill::FILL_SOLID,
              'startColor' => ['rgb' => 'D3D3D3'],
            ],
            'alignment' => [
              'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
          ];

          $sheet->getStyle('A' . $highestRow . ':G' . $highestRow)->applyFromArray($totalRowStyle);
        }

        // Aplicar formato numérico a las columnas de horas (D, E, F, G)
        if ($highestRow > 1) {
          $sheet->getStyle('D2:G' . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('0.00');
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Resumen';
  }
}