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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElectronicDocumentsReportExport implements
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

  public function __construct(Collection $data, string $title = 'Reporte de Documentos Electrónicos')
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
      // Columnas de control
      'DOC ID',
      'NÚMERO',

      // Columnas de cabecera del documento
      'TIPO DOCUMENTO',
      'FECHA EMISIÓN',
      'FECHA VENCIMIENTO',
      'NRO DOC CLIENTE',
      'TIPO DOC CLIENTE',
      'CLIENTE',
      'DIRECCIÓN',
      'EMAIL',
      'MONEDA',
      'T/C',
      'TOTAL GRAVADA',
      'TOTAL INAFECTA',
      'TOTAL EXONERADA',
      'TOTAL IGV',
      'TOTAL',
      'ÁREA',
      'SEDE',
      'ESTADO',
      'ACEPTADA SUNAT',
      'CREADO POR',
      'FECHA CREACIÓN',
    ];
  }

  public function map($row): array
  {
    return [
      $row['documento_id'],
      $row['full_number'],
      $row['tipo_documento'],
      $row['fecha_emision'],
      $row['fecha_vencimiento'],
      $row['cliente_documento'],
      $row['tipo_doc_cliente'],
      $row['cliente_nombre'],
      $row['cliente_direccion'],
      $row['cliente_email'],
      $row['moneda'],
      $row['tipo_cambio'],
      $row['total_gravada'],
      $row['total_inafecta'],
      $row['total_exonerada'],
      $row['total_igv'],
      $row['total'],
      $row['area'],
      $row['sede'],
      $row['estado'],
      $row['aceptada_sunat'],
      $row['creado_por'],
      $row['fecha_creacion'],
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
        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();

        // Habilitar filtros en la fila de encabezado (columnas A-W, 23 columnas)
        $sheet->setAutoFilter('A1:W1');

        // Aplicar formato de número a las columnas monetarias (columnas M-Q: totales)
        $currencyColumns = ['M', 'N', 'O', 'P', 'Q'];
        foreach ($currencyColumns as $column) {
          $sheet->getStyle($column . '2:' . $column . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        }

        // Formato de número para tipo de cambio (columna L)
        $sheet->getStyle('L2:L' . $highestRow)
          ->getNumberFormat()
          ->setFormatCode('#,##0.000');

        // Estilos para SI (verde con letra blanca)
        $styleGreen = [
          'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '00B050'],
          ],
          'font' => [
            'color' => ['rgb' => 'FFFFFF'],
            'bold' => true,
          ],
          'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          ],
        ];

        // Estilos para NO (rojo con letra blanca)
        $styleRed = [
          'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FF0000'],
          ],
          'font' => [
            'color' => ['rgb' => 'FFFFFF'],
            'bold' => true,
          ],
          'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          ],
        ];

        // Aplicar estilos condicionales a la columna ACEPTADA SUNAT (columna U)
        for ($row = 2; $row <= $highestRow; $row++) {
          $cellValue = $sheet->getCell('U' . $row)->getValue();

          if ($cellValue === 'SI') {
            $sheet->getStyle('U' . $row)->applyFromArray($styleGreen);
          } elseif ($cellValue === 'NO') {
            $sheet->getStyle('U' . $row)->applyFromArray($styleRed);
          }
        }

        // Alineación a la izquierda para cliente (columna H)
        $sheet->getStyle('H2:H' . $highestRow)
          ->getAlignment()
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Ocultar columna DOC ID (A) ya que es para control interno
        $sheet->getColumnDimension('A')->setVisible(false);

        $sheet->setSelectedCells('B1');
      },
    ];
  }

  public function title(): string
  {
    return 'Documentos Electrónicos';
  }
}
