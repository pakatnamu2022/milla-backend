<?php

namespace App\Exports\ap\comercial;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DiscountDynamicsReportExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $data;
  private string $currencyFormat;

  private const LAST_COL = 'S';
  private const TOTAL_COLS = 19;

  private const COLUMN_ALIGNMENT = [
    'A' => Alignment::HORIZONTAL_CENTER, // N° Cotización
    'B' => Alignment::HORIZONTAL_CENTER, // Fecha Cotización
    'C' => Alignment::HORIZONTAL_LEFT,   // Cliente
    'D' => Alignment::HORIZONTAL_CENTER, // VIN
    'E' => Alignment::HORIZONTAL_RIGHT,  // Precio Venta
    'F' => Alignment::HORIZONTAL_CENTER, // Tipo Descuento
    'G' => Alignment::HORIZONTAL_LEFT,   // Tipo Concepto
    'H' => Alignment::HORIZONTAL_LEFT,   // Concepto Descuento
    'I' => Alignment::HORIZONTAL_RIGHT,  // Descuento S/I
    'J' => Alignment::HORIZONTAL_RIGHT,  // Descuento C/I
    'K' => Alignment::HORIZONTAL_CENTER, // N° Factura
    'L' => Alignment::HORIZONTAL_CENTER, // Fecha Factura
    'M' => Alignment::HORIZONTAL_RIGHT,  // Total Factura
    'N' => Alignment::HORIZONTAL_LEFT,   // Ítem Descripción
    'O' => Alignment::HORIZONTAL_RIGHT,  // Valor Unitario (Bruto)
    'P' => Alignment::HORIZONTAL_RIGHT,  // Descuento Unitario (Dynamics)
    'Q' => Alignment::HORIZONTAL_RIGHT,  // Subtotal
    'R' => Alignment::HORIZONTAL_CENTER, // Artículo Dynamics
    'S' => Alignment::HORIZONTAL_CENTER, // Enviado Dynamics
  ];

  private const CURRENCY_COLUMNS = ['E', 'I', 'J', 'M', 'O', 'P', 'Q'];

  public function __construct(Collection $data)
  {
    $this->data = $data;
    $symbol = $data->first()?->currency_symbol ?? 'S/';
    $this->currencyFormat = '_("' . $symbol . '"* #,##0.00_);_("' . $symbol . '"* (#,##0.00);_("' . $symbol . '"* "-"??_);_(@_)';
  }

  public function collection(): Collection
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'N° COTIZACIÓN',
      'FECHA COT.',
      'CLIENTE',
      'VIN',
      'PRECIO VENTA',
      'TIPO DESCUENTO',
      'TIPO CONCEPTO',
      'CONCEPTO DESCUENTO',
      'DESCUENTO S/IGV',
      'DESCUENTO C/IGV',
      'N° FACTURA',
      'FECHA FACTURA',
      'TOTAL FACTURA',
      'ÍTEM DESCRIPCIÓN',
      'VALOR UNIT. (BRUTO)',
      'DSCTO. UNIT. DYNAMICS',
      'SUBTOTAL ÍTEM',
      'ARTÍCULO DYNAMICS',
      'SYNC DYNAMICS',
    ];
  }

  public function map($row): array
  {
    $syncStatus = match (true) {
      $row->was_dyn_requested && $row->migration_status === 'completed' => 'COMPLETADO',
      $row->was_dyn_requested && $row->migration_status === 'in_progress' => 'EN PROGRESO',
      $row->was_dyn_requested && $row->migration_status === 'failed' => 'FALLIDO',
      $row->was_dyn_requested => strtoupper($row->migration_status ?? 'SOLICITADO'),
      default => 'NO ENVIADO',
    };

    return [
      $row->correlative ?? '',
      $row->quote_date ?? '',
      $row->client ?? '',
      $row->vin ?? '',
      (float) ($row->sale_price ?? 0),
      $row->discount_type ?? '',
      $row->discount_concept_type ?? '',
      $row->discount_concept ?? '',
      (float) ($row->discount_sin_igv ?? 0),
      (float) ($row->discount_con_igv ?? 0),
      $row->invoice_number ?? '',
      $row->invoice_date ?? '',
      (float) ($row->invoice_total ?? 0),
      $row->item_description ?? '',
      (float) ($row->item_valor_unitario ?? 0),
      (float) ($row->item_descuento_unitario ?? 0),
      (float) ($row->item_subtotal ?? 0),
      $row->item_articulo_id ?? '',
      $syncStatus,
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => [
        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A237E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
      ],
    ];
  }

  public function title(): string
  {
    return 'Descuentos Dynamics';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $lastRow = $sheet->getHighestRow();

        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->getStyle('A1:' . self::LAST_COL . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color'       => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        if ($lastRow >= 2) {
          foreach (self::COLUMN_ALIGNMENT as $col => $horizontal) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getAlignment()->setHorizontal($horizontal);
          }

          foreach (self::CURRENCY_COLUMNS as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode($this->currencyFormat);
          }

          // Colorear la columna SYNC DYNAMICS según estado
          for ($row = 2; $row <= $lastRow; $row++) {
            $cell = $sheet->getCell("S{$row}");
            $val  = $cell->getValue();
            $color = match ($val) {
              'COMPLETADO'   => 'C8E6C9',
              'EN PROGRESO'  => 'FFF9C4',
              'FALLIDO'      => 'FFCDD2',
              'NO ENVIADO'   => 'F5F5F5',
              default        => 'FFFFFF',
            };
            $sheet->getStyle("S{$row}")->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($color);
          }
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . self::LAST_COL . '1');
        $sheet->setSelectedCells('A1');
      },
    ];
  }
}
