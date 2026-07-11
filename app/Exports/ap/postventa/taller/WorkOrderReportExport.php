<?php

namespace App\Exports\ap\postventa\taller;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkOrderReportExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle
{
  protected Collection $data;
  protected string $title;

  public function __construct(Collection $data, string $title = 'Reporte Órdenes de Trabajo')
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
      'TIPO DE DOCUMENTO',
      'NÚMERO DE DOCUMENTO',
      'NOMBRE COMPLETO O RAZON SOCIAL',
      'TIPO DE CLIENTE',
      'EMAIL',
      'NÚMERO TELEFONICO',
      'MARCA',
      'MODELO DEL VEHICULO',
      'KILOMETRAJE',
      'PLACA',
      'VIN',
      'CONCESIONARIO',
      'TIPO DE INGRESO',
      'NÚMERO DE OT',
      'TIPO DE SERVICIO',
      'OT INICIAL (REINGRESO)',
      'DETALLE',
      'ASESOR DE SERVICIO',
      'NOMBRE DEL TÉCNICO',
      'FECHA DE APERTURA OT',
      'HORA APERTURA OT',
      'FECHA DE CIERRE OT',
      'HORA DE CIERRE OT',
      'PRECIO M. OBRA ($)',
      'PRECIO REPUESTO ($)',
      'PRECIO LUBRICANTES ($)',
      'PRECIO TRABAJO EXTERNO ($)',
      'PRECIO INSUMO ($)',
      'PRECIO TOTAL ($)',
      'AUTORIZACION DE DATOS PERSONALES',
    ];
  }

  public function map($row): array
  {
    return [
      $row['tipo_documento'],
      $row['numero_documento'],
      $row['nombre_completo_razon_social'],
      $row['tipo_cliente'],
      $row['email'],
      $row['numero_telefonico'],
      $row['marca'],
      $row['modelo_vehiculo'],
      $row['kilometraje'],
      $row['placa'],
      $row['vin'],
      $row['concesionario'],
      $row['tipo_ingreso'],
      $row['numero_ot'],
      $row['tipo_servicio'],
      $row['ot_inicial_reingreso'],
      $row['detalle'],
      $row['asesor_servicio'],
      $row['nombre_tecnico'],
      $row['fecha_apertura_ot'],
      $row['hora_apertura_ot'],
      $row['fecha_cierre_ot'],
      $row['hora_cierre_ot'],
      $row['precio_mano_obra'],
      $row['precio_repuesto'],
      $row['precio_lubricantes'],
      $row['precio_trabajo_externo'],
      $row['precio_insumo'],
      $row['precio_total'],
      $row['autorizacion_datos_personales'],
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
    return 'Reporte OT';
  }
}