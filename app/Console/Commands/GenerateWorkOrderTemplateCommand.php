<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GenerateWorkOrderTemplateCommand extends Command
{
  protected $signature = 'generate:work-order-template';
  protected $description = 'Genera plantilla Excel limpia para importar órdenes de trabajo históricas';

  public function handle()
  {
    try {
      $this->info('Generando plantilla Excel...');

      $spreadsheet = new Spreadsheet();

      // Crear 5 pestañas
      $this->createSheet1($spreadsheet->getActiveSheet());
      $this->createSheet2($spreadsheet->createSheet());
      $this->createSheet3($spreadsheet->createSheet());
      $this->createSheet4($spreadsheet->createSheet());
      $this->createSheet5($spreadsheet->createSheet());

      // Guardar
      $filePath = storage_path('app/plantilla_work_orders_historico.xlsx');
      $writer = new Xlsx($spreadsheet);
      $writer->save($filePath);

      $this->info("✓ Plantilla generada: {$filePath}");
      return 0;
    } catch (\Exception $e) {
      $this->error("Error: " . $e->getMessage());
      return 1;
    }
  }

  private function createSheet1($sheet)
  {
    $sheet->setTitle('1_ApWorkOrder');

    $headers = [
      'vehicle_id', 'currency_id', 'sede_id', 'advisor_id', 'status_id',
      'opening_date', 'mileage', 'invoice_to', 'exchange_rate',
      'estimated_delivery_date', 'actual_delivery_date', 'observations',
      'total_labor_cost', 'total_parts_cost', 'subtotal',
      'discount_amount', 'tax_amount', 'final_amount',
      'is_invoiced', 'created_by'
    ];

    $desc = [
      'ID vehículo *', '1=USD 3=PEN *', 'ID Sede *', 'ID Asesor *', '893',
      'YYYY-MM-DD *', 'KM', 'ID Cliente', 'Si USD',
      'YYYY-MM-DD HH:MM', 'YYYY-MM-DD HH:MM', 'Observaciones',
      'MO', 'Repuestos', 'Subtotal',
      'Descuento', 'IGV 18%', 'Total',
      '1/0', 'ID User *'
    ];

    $this->writeHeaders($sheet, $headers, $desc);
  }

  private function createSheet2($sheet)
  {
    $sheet->setTitle('2_ApWorkOrderItem');

    $headers = ['work_order_correlative', 'group_number', 'type_planning_id', 'type_operation_id', 'description'];
    $desc = ['Correlativo OT *', 'Grupo', 'ID Tipo planif *', 'ID Tipo oper', 'Descripción *'];

    $this->writeHeaders($sheet, $headers, $desc);
  }

  private function createSheet3($sheet)
  {
    $sheet->setTitle('3_ApWorkOrderParts');

    $headers = [
      'work_order_correlative', 'group_number', 'product_id', 'warehouse_id',
      'quantity_used', 'unit_price', 'discount_percentage',
      'total_cost', 'net_amount', 'tax_amount'
    ];

    $desc = [
      'Correlativo OT *', 'Grupo', 'ID Producto *', 'ID Almacén',
      'Cantidad *', 'Precio *', '% Desc',
      'cant*precio', 'total-desc', 'neto*0.18'
    ];

    $this->writeHeaders($sheet, $headers, $desc);
  }

  private function createSheet4($sheet)
  {
    $sheet->setTitle('4_WorkOrderLabour');

    $headers = [
      'work_order_correlative', 'group_number', 'description', 'labour_type',
      'time_spent', 'hourly_rate', 'discount_percentage',
      'total_cost', 'net_amount', 'tax_amount'
    ];

    $desc = [
      'Correlativo OT *', 'Grupo', 'Descripción *', 'labor/material/deductible *',
      'HH:MM:SS *', 'Tarifa *', '% Desc',
      'hrs*tarifa', 'total-desc', 'neto*0.18'
    ];

    $this->writeHeaders($sheet, $headers, $desc);
  }

  private function createSheet5($sheet)
  {
    $sheet->setTitle('5_ApWorkOrderPlanning');

    $headers = ['work_order_correlative', 'worker_id', 'group_number', 'estimated_hours', 'status'];
    $desc = ['Correlativo OT *', 'ID Operario *', 'Grupo', 'Horas est', 'completed'];

    $this->writeHeaders($sheet, $headers, $desc);
  }

  private function writeHeaders($sheet, $headers, $descriptions)
  {
    // Headers
    foreach ($headers as $i => $header) {
      $col = chr(65 + $i);
      $sheet->setCellValue($col . '1', $header);
      $sheet->setCellValue($col . '2', $descriptions[$i]);
      $sheet->getColumnDimension($col)->setWidth(18);
    }

    // Estilos
    $lastCol = chr(64 + count($headers));

    // Fila 1: header azul
    $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    // Fila 2: descripción gris
    $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
      'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ]);

    // Bordes
    $sheet->getStyle('A1:' . $lastCol . '2')->applyFromArray([
      'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
    ]);

    $sheet->getRowDimension(1)->setRowHeight(25);
    $sheet->getRowDimension(2)->setRowHeight(30);
    $sheet->freezePane('A3');
  }
}