<?php

namespace App\Exports\ap\postventa\meson;

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

class MesonInvoicingReportExport implements
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

  public function __construct(Collection $data, string $title = 'Reporte de Facturación Mesón')
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
      'SEDE',
      'NÚMERO COTIZACIÓN',
      'TIPO COMPROBANTE',
      'FECHA EMISIÓN',
      'SERIE',
      'NÚMERO',
      'CÓDIGO ARTÍCULO',
      'NOMBRE ARTÍCULO',
      'CANTIDAD',
      'PVP',
      'DESCUENTO %',
      'NETO',
      'COSTO',
      'BENEFICIO',
      '% BENEFICIO',
      'COMISIÓN',
      'CLIENTE',
      'NÚMERO DOCUMENTO',
      'MONEDA',
      'MONEDA ORIGINAL',
    ];
  }

  public function map($row): array
  {
    return [
      $row['sede'],
      $row['numero_cotizacion'],
      $row['tipo_comprobante'],
      $row['fecha_emision'],
      $row['serie_comprobante'],
      $row['numero_comprobante'],
      $row['codigo_articulo'],
      $row['nombre_articulo'],
      $row['cantidad'],
      $row['pvp'],
      $row['descuento_porcentaje'],
      $row['neto'],
      $row['costo'],
      $row['beneficio'],
      $row['porcentaje_beneficio'],
      $row['comision'],
      $row['cliente'],
      $row['numero_documento_cliente'],
      $row['moneda'],
      $row['moneda_original'],
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
        // Habilitar filtros en la fila de encabezado (columnas A-T, 20 columnas)
        $event->sheet->getDelegate()->setAutoFilter('A1:T1');
      },
    ];
  }

  public function title(): string
  {
    return $this->title;
  }
}