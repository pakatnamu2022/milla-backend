<?php

namespace App\Exports\ap\postventa\taller;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class OrderQuotationExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected array $quotation;

  public function __construct(array $quotation)
  {
    $this->quotation = $quotation;
  }

  public function collection()
  {
    // Retornar los detalles de la cotización como colección
    return collect($this->quotation['details']);
  }

  public function headings(): array
  {
    return [
      'Código',
      'Descripción',
      'Tipo',
      'Cantidad',
      'Precio Unit.',
      '% Descuento',
      'Importe Neto'
    ];
  }

  public function map($detail): array
  {
    return [
      $this->quotation['show_codes'] ? ($detail['code'] ?? '') : '',
      $detail['description'] ?? '',
      $detail['item_type'] ?? '',
      $detail['quantity'] ?? 0,
      $detail['unit_price'] ?? 0,
      $detail['discount'] ?? 0,
      $detail['total_amount'] ?? 0,
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'size' => 12,
          'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => ['rgb' => '4472C4']
        ],
        'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER
        ]
      ]
    ];
  }

  public function title(): string
  {
    return 'Cotizacion';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Información de la cotización en las primeras filas
        $sheet->insertNewRowBefore(1, 10);

        // Título
        $sheet->setCellValue('A1', 'COTIZACIÓN');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
          'font' => ['bold' => true, 'size' => 16],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Información básica
        $row = 2;
        $sheet->setCellValue("A{$row}", 'N° Cotización:');
        $sheet->setCellValue("B{$row}", $this->quotation['quotation_number']);
        $sheet->setCellValue("D{$row}", 'Fecha:');
        $sheet->setCellValue("E{$row}", \Carbon\Carbon::parse($this->quotation['quotation_date'])->format('d/m/Y'));

        $row++;
        $sheet->setCellValue("A{$row}", 'Cliente:');
        $sheet->setCellValue("B{$row}", $this->quotation['customer_name']);
        $sheet->setCellValue("D{$row}", 'Documento:');
        $sheet->setCellValue("E{$row}", $this->quotation['customer_document']);

        $row++;
        $sheet->setCellValue("A{$row}", 'Vehículo:');
        $sheet->setCellValue("B{$row}", $this->quotation['vehicle_brand'] . ' ' . $this->quotation['vehicle_model']);
        $sheet->setCellValue("D{$row}", 'Placa:');
        $sheet->setCellValue("E{$row}", $this->quotation['vehicle_plate']);

        $row++;
        $sheet->setCellValue("A{$row}", 'Asesor:');
        $sheet->setCellValue("B{$row}", $this->quotation['advisor_name']);

        $row++;
        $sheet->setCellValue("A{$row}", 'Observaciones:');
        $sheet->setCellValue("B{$row}", $this->quotation['observations']);
        $sheet->mergeCells("B{$row}:G{$row}");

        // Estilo para las etiquetas
        for ($i = 2; $i <= 6; $i++) {
          $sheet->getStyle("A{$i}")->getFont()->setBold(true);
          $sheet->getStyle("D{$i}")->getFont()->setBold(true);
        }

        // Congelar la fila 11 (encabezados de la tabla)
        $sheet->freezePane('A12');

        // Auto filtro en los encabezados (fila 11)
        $lastRow = $sheet->getHighestRow();
        $sheet->setAutoFilter("A11:G11");

        // Agregar totales al final
        $lastRow = $sheet->getHighestRow();
        $totalRow = $lastRow + 2;

        // Obtener el símbolo de moneda
        $currencySymbol = 'S/';
        if (isset($this->quotation['type_currency']) && is_object($this->quotation['type_currency'])) {
          $currencySymbol = $this->quotation['type_currency']->symbol ?? 'S/';
        } elseif (isset($this->quotation['currency_symbol'])) {
          $currencySymbol = $this->quotation['currency_symbol'];
        }

        // Verificar si tiene totales separados (cotización normal) o solo subtotal (repuestos)
        if (isset($this->quotation['total_labor']) && isset($this->quotation['total_parts'])) {
          // Cotización normal con mano de obra y repuestos
          $sheet->setCellValue("F{$totalRow}", 'Total M.O.:');
          $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['total_labor'], 2));
          $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);

          $totalRow++;
          $sheet->setCellValue("F{$totalRow}", 'Total Repuestos:');
          $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['total_parts'], 2));
          $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);

          $totalRow++;
          $sheet->setCellValue("F{$totalRow}", 'Total Descuentos:');
          $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['total_discounts'], 2));
          $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);

          $totalRow++;
          $sheet->setCellValue("F{$totalRow}", 'Base Imponible:');
          $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['base_imponible'] ?? 0, 2));
          $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
        } else {
          // Cotización de solo repuestos
          if (isset($this->quotation['subtotal'])) {
            $sheet->setCellValue("F{$totalRow}", 'Subtotal:');
            $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['subtotal'], 2));
            $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
            $totalRow++;
          }

          if (isset($this->quotation['total_discounts'])) {
            $sheet->setCellValue("F{$totalRow}", 'Total Descuentos:');
            $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['total_discounts'], 2));
            $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
            $totalRow++;
          }

          if (isset($this->quotation['op_gravada'])) {
            $sheet->setCellValue("F{$totalRow}", 'OP. Gravadas:');
            $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['op_gravada'], 2));
            $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);
            $totalRow++;
          }
        }

        // IGV y Total (comunes a ambos)
        $sheet->setCellValue("F{$totalRow}", 'IGV 18%:');
        $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['tax_amount'], 2));
        $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);

        $totalRow++;
        $sheet->setCellValue("F{$totalRow}", 'TOTAL:');
        $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['total_amount'], 2));
        $sheet->getStyle("F{$totalRow}:G{$totalRow}")->applyFromArray([
          'font' => ['bold' => true, 'size' => 12],
          'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E7E6E6']
          ]
        ]);

        // Si hay saldo pendiente (solo en repuestos), agregarlo
        if (isset($this->quotation['saldo_pendiente'])) {
          $totalRow += 2;
          $sheet->setCellValue("F{$totalRow}", 'Total Pagado:');
          $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['total_pagado'] ?? 0, 2));
          $sheet->getStyle("F{$totalRow}:G{$totalRow}")->getFont()->setBold(true);

          $totalRow++;
          $sheet->setCellValue("F{$totalRow}", 'Saldo Pendiente:');
          $sheet->setCellValue("G{$totalRow}", $currencySymbol . ' ' . number_format($this->quotation['saldo_pendiente'], 2));
          $sheet->getStyle("F{$totalRow}:G{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FF0000']],
            'fill' => [
              'fillType' => Fill::FILL_SOLID,
              'startColor' => ['rgb' => 'FFFF00']
            ]
          ]);
        }

        // Aplicar formato de número a las columnas numéricas
        $highestRow = $sheet->getHighestRow();
        for ($row = 11; $row <= $lastRow; $row++) {
          $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
      },
    ];
  }
}