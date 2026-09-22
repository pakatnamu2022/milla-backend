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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OrderQuotationListExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $quotations;
  protected string $title;

  public function __construct(Collection $quotations, string $title = 'Reporte de Cotizaciones')
  {
    $this->quotations = $quotations;
    $this->title = $title;
  }

  public function collection()
  {
    return $this->quotations;
  }

  public function headings(): array
  {
    return [
      'Sede',
      'N° Cotización',
      'Estado',
      'Fecha Cotización',
      'Fecha Expiración',
      'Fecha Recojo',
      'Placa Vehículo',
      'VIN Vehículo',
      'Marca',
      'Modelo',
      'Cliente',
      'Moneda',
      'Subtotal',
      'Descuento',
      'IGV',
      'Total',
      'Creado Por',
      'Fecha Creación',
      'Descartado Por',
      'Fecha Descarte',
      'Observaciones',
    ];
  }

  public function map($quotation): array
  {
    return [
      $quotation->sede?->abreviatura ?? '',
      $quotation->quotation_number,
      $quotation->status?->description ?? '',
      $quotation->quotation_date ? $quotation->quotation_date->format('Y-m-d') : '',
      $quotation->expiration_date ? $quotation->expiration_date->format('Y-m-d') : '',
      $quotation->collection_date ? $quotation->collection_date->format('Y-m-d') : '',
      $quotation->vehicle?->plate ?? '',
      $quotation->vehicle?->vin ?? '',
      $quotation->vehicle?->model?->family?->brand?->description ?? '',
      $quotation->vehicle?->model?->version ?? '',
      $quotation->client?->full_name ?? '',
      $quotation->typeCurrency?->name ?? '',
      $quotation->subtotal ?? 0,
      $quotation->discount_amount ?? 0,
      $quotation->tax_amount ?? 0,
      $quotation->total_amount ?? 0,
      $quotation->createdBy?->name ?? '',
      $quotation->created_at ? $quotation->created_at->format('Y-m-d H:i:s') : '',
      $quotation->discardedBy?->name ?? '',
      $quotation->discarded_at ? $quotation->discarded_at->format('Y-m-d H:i:s') : '',
      $quotation->observations ?? '',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'color' => ['rgb' => 'FFFFFF'],
          'size' => 11,
        ],
        'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => ['rgb' => '4472C4'],
        ],
        'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER,
        ],
      ],
    ];
  }

  public function title(): string
  {
    return 'Cotizaciones';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Congelar la primera fila (encabezados)
        $sheet->freezePane('A2');

        // Habilitar filtros en la fila de encabezado
        $highestColumn = $sheet->getHighestColumn();
        $sheet->setAutoFilter("A1:{$highestColumn}1");

        // Aplicar formato de número a las columnas numéricas
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
          // Subtotal (columna M)
          $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          // Descuento (columna N)
          $sheet->getStyle("N{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          // IGV (columna O)
          $sheet->getStyle("O{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          // Total (columna P)
          $sheet->getStyle("P{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
      },
    ];
  }
}
