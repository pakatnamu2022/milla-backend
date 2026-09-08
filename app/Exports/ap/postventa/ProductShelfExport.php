<?php

namespace App\Exports\ap\postventa;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductShelfExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $data;
  protected string $title;

  public function __construct(Collection $data, string $title = 'Productos por Estante')
  {
    $this->data = $data;
    $this->title = $title;
  }

  public function collection()
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'CÓDIGO ESTANTE',
      'ETIQUETA ESTANTE',
      'POSICIÓN',
      'CÓDIGO REPUESTO',
      'CÓDIGO DYN',
      'NOMBRE REPUESTO',
      'CATEGORÍA',
      'MARCA',
      'UNIDAD MEDIDA',
    ];
  }

  public function map($row): array
  {
    return [
      $row['shelf_code'],
      $row['shelf_label'],
      $row['position'],
      $row['product_code'],
      $row['product_dyn_code'],
      $row['product_name'],
      $row['category'],
      $row['brand'],
      $row['unit_measurement'],
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
        // Habilitar filtros en la fila de encabezado
        $event->sheet->getDelegate()->setAutoFilter('A1:I1');

        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();

        // Alineación central para todas las columnas
        $sheet->getStyle('A2:I' . $highestRow)
          ->getAlignment()
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Productos por Estante';
  }
}
