<?php

namespace App\Exports\ap\postventa;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryOutputReportExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle
{
  protected Collection $data;
  protected string $title;

  public function __construct(Collection $data, string $title = 'Reporte Salidas de Productos')
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
      'FECHA FACTURA FINAL',
      'CODIGO AFS',
      'CONCESIONARIO',
      'CODIGO PRODUCTO',
      'NUMERO',
      'PVP',
      'MARGEN',
      'AREA',
      'DOCUMENTO',
      'NOMBRE CLIENTE',
    ];
  }

  public function map($row): array
  {
    return [
      $row['fecha_factura_final'],
      $row['codigo_afs'],
      $row['concesionario'],
      $row['codigo_producto'],
      $row['numero'],
      $row['pvp'],
      $row['margen'],
      $row['area'],
      $row['documento'],
      $row['nombre_cliente'],
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
          'startColor' => ['rgb' => '28A745'],
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
      ],
    ];
  }

  public function title(): string
  {
    return 'Salidas Inventario';
  }
}