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

class BilledHoursDetailSheet implements
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
      'DESCRIPCIÓN LABOUR',
      'CATEGORÍA',
      'HORAS FACTURADAS TOTAL',
      'CANTIDAD TÉCNICOS',
      'DNI TÉCNICO',
      'NOMBRE TÉCNICO',
      'HORAS TRABAJADAS',
      'HORAS ASIGNADAS (IGUAL)',
    ];
  }

  public function map($row): array
  {
    return [
      $row['sede'],
      $row['numero_ot'],
      $row['descripcion_labour'],
      $row['categoria_tipo'],
      $row['horas_facturadas_total'],
      $row['cantidad_tecnicos'],
      $row['dni_tecnico'],
      $row['nombre_tecnico'],
      $row['horas_trabajadas'],
      $row['horas_asignadas'],
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
          'startColor' => ['rgb' => '70AD47'],
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

        // Aplicar formato numérico a las columnas de horas (E, I, J)
        if ($highestRow > 1) {
          $sheet->getStyle('E2:E' . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('0.00');

          $sheet->getStyle('I2:J' . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('0.00');
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Detalle Horas Facturadas';
  }
}