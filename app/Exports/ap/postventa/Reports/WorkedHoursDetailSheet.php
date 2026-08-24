<?php

namespace App\Exports\ap\postventa\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkedHoursDetailSheet implements
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
      'NÚMERO OT',
      'DNI TÉCNICO',
      'NOMBRE TÉCNICO',
      'TIPO PLANIFICACIÓN',
      'CATEGORÍA',
      'DESCRIPCIÓN',
      'HORAS TRABAJADAS',
      'FECHA INICIO',
      'FECHA FINALIZACIÓN',
    ];
  }

  public function map($row): array
  {
    return [
      $row['sede'],
      $row['numero_ot'],
      $row['dni_tecnico'],
      $row['nombre_tecnico'],
      $row['tipo_planificacion'],
      $row['categoria_tipo'],
      $row['descripcion_item'],
      $row['horas_trabajadas'],
      $row['fecha_inicio'],
      $row['fecha_finalizacion'],
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
          'startColor' => ['rgb' => '2E75B6'],
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
        $sheet->setAutoFilter('A1:J1');

        // Aplicar formato numérico a la columna de horas trabajadas (H)
        if ($highestRow > 1) {
          $sheet->getStyle('H2:H' . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('0.00');
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Detalle Horas Trabajadas';
  }
}
