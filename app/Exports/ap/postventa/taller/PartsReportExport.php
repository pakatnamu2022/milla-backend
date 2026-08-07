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

class PartsReportExport implements
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
  protected bool $amountsInSoles;

  public function __construct(Collection $data, string $title = 'Reporte Repuestos OT', bool $amountsInSoles = false)
  {
    $this->data = $data;
    $this->title = $title;
    $this->amountsInSoles = $amountsInSoles;
  }

  public function collection()
  {
    return $this->data;
  }

  public function headings(): array
  {
    $currency = $this->amountsInSoles ? 'S/' : '$';

    return [
      'NÚMERO DE OT',
      'FECHA DE APERTURA OT',
      'FECHA DE CIERRE OT',
      'TIPO DE SERVICIO',
      'COD REPUESTO',
      'NOMBRE REPUESTO',
      'CANTIDAD',
      "PVP ({$currency})",
      'DESC (%)',
      "NETO ({$currency})",
      "COSTO ({$currency})",
      "BENEFICIO ({$currency})",
    ];
  }

  public function map($row): array
  {
    return [
      $row['numero_ot'],
      $row['fecha_apertura_ot'],
      $row['fecha_cierre_ot'],
      $row['tipo_servicio'],
      $row['codigo_repuesto'],
      $row['nombre_repuesto'],
      $row['cantidad'],
      $row['pvp'],
      $row['descuento'],
      $row['neto'],
      $row['costo'],
      $row['beneficio'],
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
        // Habilitar filtros en la fila de encabezado (columnas A-L, 12 columnas)
        $event->sheet->getDelegate()->setAutoFilter('A1:L1');

        // Aplicar formato de número a las columnas de moneda
        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();

        // Formato de número para las columnas H (PVP), J (NETO), K (COSTO), L (BENEFICIO)
        $currencyColumns = ['H', 'J', 'K', 'L'];
        foreach ($currencyColumns as $column) {
          $sheet->getStyle($column . '2:' . $column . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        }

        // Formato de número para CANTIDAD (columna G)
        $sheet->getStyle('G2:G' . $highestRow)
          ->getNumberFormat()
          ->setFormatCode('#,##0.00');

        // Formato de porcentaje para DESC (columna I)
        $sheet->getStyle('I2:I' . $highestRow)
          ->getNumberFormat()
          ->setFormatCode('0.00');

        // Alineación central para todas las columnas
        $sheet->getStyle('A2:L' . $highestRow)
          ->getAlignment()
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Reporte Repuestos';
  }
}