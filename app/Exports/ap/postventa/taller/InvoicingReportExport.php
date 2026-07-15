<?php

namespace App\Exports\ap\postventa\taller;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicingReportExport implements WithMultipleSheets
{
  protected Collection $data;
  protected array $summary;
  protected string $title;

  public function __construct(Collection $data, array $summary, string $title = 'Reporte de Facturación OT')
  {
    $this->data = $data;
    $this->summary = $summary;
    $this->title = $title;
  }

  /**
   * Retorna las hojas del reporte
   */
  public function sheets(): array
  {
    $sheets = [
      new InvoicingReportMainSheet($this->data, 'Facturación OT'),
    ];

    // Solo agregar hoja de resumen si hay datos
    if (!empty($this->summary)) {
      $sheets[] = new InvoicingReportSummarySheet($this->summary, 'Resumen Pendientes');
    }

    return $sheets;
  }
}

/**
 * Hoja principal con el detalle de facturación
 */
class InvoicingReportMainSheet implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle
{
  protected Collection $data;
  protected string $title;

  public function __construct(Collection $data, string $title)
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
      'TALLER',
      'NÚMERO OT',
      'FECHA APERTURA OT',
      'ESTADO',
      'ASESOR',
      'TIPO SERVICIO',
      'MARCA',
      'MODELO',
      'TRABAJO REALIZADO',
      'OPERARIO',
      'SERIE',
      'NÚMERO',
      'FECHA COMPROBANTE',
      'TIPO',
      'NUM DOC',
      'CLIENTE',
      'TOTAL M.O',
      'TOTAL REPUESTOS',
      'DESCUENTO %',
      'DESCUENTO MONTO',
      'MONTO SIN IGV',
      'IGV',
      'TOTAL',
      'MONEDA',
    ];
  }

  public function map($row): array
  {
    return [
      $row['taller'],
      $row['numero_ot'],
      $row['fecha_apertura_ot'],
      $row['estado'],
      $row['asesor_servicio'],
      $row['tipo_servicio'],
      $row['marca'],
      $row['modelo_vehiculo'],
      $row['trabajo_realizado'],
      $row['operario'],
      $row['serie_comprobante'],
      $row['numero_comprobante'],
      $row['fecha_comprobante'],
      $row['tipo'],
      $row['num_doc_cliente'],
      $row['cliente'],
      $row['total_mano_obra'],
      $row['total_repuestos'],
      $row['descuento_porcentaje'],
      $row['descuento_monto'],
      $row['monto_sin_igv'],
      $row['igv'],
      $row['total'],
      $row['moneda'],
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

  public function title(): string
  {
    return $this->title;
  }
}

/**
 * Hoja de resumen de OTs pendientes de pago
 */
class InvoicingReportSummarySheet implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle
{
  protected array $summary;
  protected string $title;

  public function __construct(array $summary, string $title)
  {
    $this->summary = $summary;
    $this->title = $title;
  }

  public function collection()
  {
    return collect($this->summary);
  }

  public function headings(): array
  {
    return [
      'TALLER',
      'NÚMERO OT',
      'CLIENTE',
      'N° ANTICIPOS',
      'SERIES Y NÚMEROS',
      'TOTAL ANTICIPOS',
      'TOTAL OT',
      'DEUDA',
    ];
  }

  public function map($row): array
  {
    return [
      $row['taller'],
      $row['numero_ot'],
      $row['cliente'],
      $row['num_anticipos'],
      $row['series_numeros'],
      $row['total_anticipos'],
      $row['total_ot'],
      $row['deuda'],
    ];
  }

  public function styles(Worksheet $sheet)
  {
    $lastRow = count($this->summary) + 1;

    $styles = [
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

    // Ajuste de texto para la columna de series y números (columna E)
    $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setWrapText(true);
    $sheet->getColumnDimension('E')->setWidth(30);

    // Estilo para la última fila (totales)
    if ($lastRow > 1) {
      $styles[$lastRow] = [
        'font' => [
          'bold' => true,
          'size' => 11,
        ],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => 'FFC107'],
        ],
        'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
        ],
      ];
    }

    return $styles;
  }

  public function title(): string
  {
    return $this->title;
  }
}
