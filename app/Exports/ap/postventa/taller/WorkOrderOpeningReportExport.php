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

class WorkOrderOpeningReportExport implements
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

  public function __construct(Collection $data, string $title = 'Reporte Apertura Órdenes de Trabajo')
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
      'MARCA',
      'MODELO DEL VEHICULO',
      'KILOMETRAJE',
      'PLACA',
      'VIN',
      'TIPO DE INGRESO',
      'NÚMERO DE OT',
      'TIPO DE SERVICIO',
      'TIPO DE OPERACIÓN',
      'ASESOR DE SERVICIO',
      'NOMBRE DEL TÉCNICO',
      'FECHA DE APERTURA OT',
      'FECHA DE CIERRE OT',
      'PRECIO TOTAL',
      'MONEDA',
    ];
  }

  public function map($row): array
  {
    return [
      $row['taller'],
      $row['marca'],
      $row['modelo_vehiculo'],
      $row['kilometraje'],
      $row['placa'],
      $row['vin'],
      $row['tipo_ingreso'],
      $row['numero_ot'],
      $row['tipo_servicio'],
      $row['tipo_operacion'],
      $row['asesor_servicio'],
      $row['nombre_tecnico'],
      $row['fecha_apertura_ot'],
      $row['fecha_cierre_ot'],
      $row['precio_total'],
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

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        // Habilitar filtros en la fila de encabezado (columnas A-P, 16 columnas)
        $event->sheet->getDelegate()->setAutoFilter('A1:P1');

        $sheet = $event->sheet->getDelegate();
        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Reporte Apertura OT';
  }
}
