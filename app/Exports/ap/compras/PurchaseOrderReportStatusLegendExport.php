<?php

namespace App\Exports\ap\compras;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PurchaseOrderReportStatusLegendExport implements
  FromArray,
  WithTitle,
  ShouldAutoSize,
  WithEvents
{
  public function array(): array
  {
    return [
      ['COLUMNA', 'ESTADO', 'SIGNIFICADO'],
      ['ESTATUS', 'LIBRE', 'El vehículo de la orden de compra aún no tiene un documento electrónico (factura/boleta) asociado, es decir, no ha sido facturado.'],
      ['ESTATUS', 'CONTADO', 'El vehículo ya fue facturado y el documento electrónico asociado indica que la venta fue al contado.'],
      ['ESTATUS', 'CREDITO', 'El vehículo ya fue facturado y el documento electrónico asociado indica que la venta fue a crédito (financiada).'],
      ['ESTADO CXP', 'PAGADO', 'La factura del proveedor asociada a la orden de compra no tiene saldo pendiente en cuentas por pagar (fue pagada en su totalidad). IMPORTANTE: en las OC de origen "STOCK INICIAL" este valor no es confiable, ver columna ORIGEN OC.'],
      ['ESTADO CXP', 'PENDIENTE', 'La factura del proveedor asociada a la orden de compra tiene un saldo pendiente de pago en cuentas por pagar (monto sin aplicar mayor a 0).'],
      ['ORIGEN OC', 'SISTEMA', 'La orden de compra fue generada dentro del sistema desde su implementación, con factura real de Dynamics (invoice_dynamics). El cruce con Cuentas por Pagar es confiable.'],
      ['ORIGEN OC', 'STOCK INICIAL', 'La orden de compra corresponde a vehículos migrados como stock inicial (prefijo OCSI-) antes de la implementación del sistema. No tiene una factura real de Dynamics vinculada, por lo que el cruce con Cuentas por Pagar no encuentra información y la columna ESTADO CXP muestra "PAGADO" por defecto sin que eso sea una confirmación real de pago. Usa esta columna para filtrar y analizar solo la data confiable generada desde la implementación del sistema.'],
    ];
  }

  public function title(): string
  {
    return 'Explicación de Estados';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:C1')->applyFromArray([
          'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->getStyle('A1:C' . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color'       => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        $sheet->getStyle('A2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setWrapText(true);

        $colors = [
          'LIBRE'     => ['bg' => '43A047', 'text' => 'FFFFFF'],
          'CONTADO'   => ['bg' => '1E88E5', 'text' => 'FFFFFF'],
          'CREDITO'   => ['bg' => 'FB8C00', 'text' => 'FFFFFF'],
          'PAGADO'    => ['bg' => 'C8E6C9', 'text' => '1B5E20'],
          'PENDIENTE' => ['bg' => 'FFCDD2', 'text' => 'B71C1C'],
          'SISTEMA'       => ['bg' => 'E1F5FE', 'text' => '01579B'],
          'STOCK INICIAL' => ['bg' => 'ECEFF1', 'text' => '546E7A'],
        ];

        for ($i = 2; $i <= $lastRow; $i++) {
          $estado = $sheet->getCell('B' . $i)->getValue();
          $color  = $colors[$estado] ?? null;

          if ($color) {
            $sheet->getStyle('B' . $i)->applyFromArray([
              'font' => ['bold' => true, 'color' => ['rgb' => $color['text']]],
              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['bg']]],
            ]);
          }

          $sheet->getRowDimension($i)->setRowHeight(30);
        }

        $sheet->getColumnDimension('C')->setWidth(90);
        $sheet->freezePane('A2');
      },
    ];
  }
}
