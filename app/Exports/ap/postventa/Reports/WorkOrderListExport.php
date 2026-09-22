<?php

namespace App\Exports\ap\postventa\Reports;

use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
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

class WorkOrderListExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $workOrders;
  protected string $title;

  // Mapeo de columnas a sus índices (basado en 1)
  private const COL_TIENE_ANTICIPO = 18;  // Columna R
  private const COL_ESTADO_SUNAT = 22;    // Columna V
  private const COL_CONTABILIZADA = 23;   // Columna W

  public function __construct(Collection $workOrders, string $title = 'Reporte de Órdenes de Trabajo')
  {
    $this->workOrders = $workOrders;
    $this->title = $title;
  }

  public function collection()
  {
    return $this->workOrders;
  }

  public function headings(): array
  {
    return [
      'Sede',
      'Correlativo',
      'Estado',
      'Fecha Apertura',
      'Fecha Entrega Estimada',
      'Placa Vehículo',
      'VIN Vehículo',
      'Marca',
      'Modelo',
      'KM',
      'Asesor',
      'Tipo de Planificación',
      'Operación',
      'Descripción',
      'Moneda',
      'Cliente Facturar',
      'Total',
      'Tiene Anticipo',
      'Anticipos',
      'Total Anticipos',
      'Comprobante Final',
      'Estado SUNAT',
      'Contabilizada',
    ];
  }

  public function map($workOrder): array
  {
    // Obtener el primer item no eliminado
    $firstItem = $workOrder->items->first();

    // Obtener y formatear anticipos
    [$tieneAnticipo, $advancesFormatted, $totalAdvances] = $this->formatAdvances($workOrder);

    // Determinar el documento electrónico
    $electronicDocument = $this->getElectronicDocument($workOrder, $firstItem);

    return [
      $workOrder->sede?->abreviatura ?? '',
      $workOrder->correlative,
      $workOrder->status?->description ?? '',
      $workOrder->opening_date ? $workOrder->opening_date->format('Y-m-d') : '',
      $workOrder->estimated_delivery_date ? $workOrder->estimated_delivery_date->format('Y-m-d H:i:s') : '',
      $workOrder->vehicle?->plate ?? '',
      $workOrder->vehicle?->vin ?? '',
      $workOrder->vehicle?->model?->family?->brand?->name ?? '',
      $workOrder->vehicle?->model?->version ?? '',
      $workOrder->mileage ?? '',
      $workOrder->advisor?->nombre_completo ?? '',
      $firstItem?->typePlanning?->description ?? '',
      $firstItem?->typeOperation?->description ?? '',
      $firstItem?->description ?? '',
      $workOrder->typeCurrency?->symbol ?? '',
      $workOrder->invoiceTo?->full_name ?? '',
      $workOrder->final_amount ?? 0,
      $tieneAnticipo,
      $advancesFormatted,
      $totalAdvances,
      $electronicDocument?->full_number ?? '-',
      $electronicDocument ? ($electronicDocument->aceptada_por_sunat ? 'SI' : 'NO') : '-',
      $electronicDocument ? ($electronicDocument->is_accounted ? 'SI' : 'NO') : '-',
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
    return 'Órdenes de Trabajo';
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

        // Aplicar formato de número a las columnas numéricas y reglas de color
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
          // Total (columna Q - índice 17)
          $sheet->getStyle("Q{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          // Total Anticipos (columna T - índice 20)
          $sheet->getStyle("T{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

          // Aplicar reglas de color condicionales
          $this->applyColorRules($sheet, $row);
        }
      },
    ];
  }

  /**
   * Formatea los anticipos de la orden de trabajo
   *
   * @param mixed $workOrder
   * @return array [tieneAnticipo, advancesFormatted, totalAdvances]
   */
  private function formatAdvances($workOrder): array
  {
    $activeAdvances = $workOrder->getActiveAdvances();

    $advancesFormatted = '-';
    $totalAdvances = 0;
    $tieneAnticipo = 'NO';

    if ($activeAdvances && $activeAdvances->count() > 0) {
      $tieneAnticipo = 'SI';
      $advancesArray = [];

      foreach ($activeAdvances as $advance) {
        $sunatStatus = $advance->aceptada_por_sunat == 1 ? '(SI)' : '(NO)';
        $advancesArray[] = $advance->full_number . ' ' . $sunatStatus;
        $totalAdvances += $advance->total ?? 0;
      }

      $advancesFormatted = implode(' | ', $advancesArray);
    }

    return [$tieneAnticipo, $advancesFormatted, $totalAdvances];
  }

  /**
   * Determina el documento electrónico a usar
   *
   * @param mixed $workOrder
   * @param mixed $firstItem
   * @return mixed
   */
  private function getElectronicDocument($workOrder, $firstItem)
  {
    $electronicDocument = null;

    // Si el tipo de documento es INTERNA_CC, obtener el documento desde la nota interna
    if ($firstItem && $firstItem->typePlanning && $firstItem->typePlanning->type_document === TypePlanningWorkOrder::INTERNA_CC) {
      $internalNote = $workOrder->internalNote;
      if ($internalNote) {
        $electronicDocument = $internalNote->electronicDocuments()->first();
      }
    }

    // Si no se encontró documento desde nota interna, usar getFinalInvoice()
    if (!$electronicDocument) {
      $electronicDocument = $workOrder->getFinalInvoice();
    }

    return $electronicDocument;
  }

  /**
   * Aplica las reglas de color condicionales a las celdas
   *
   * @param Worksheet $sheet
   * @param int $row
   */
  private function applyColorRules(Worksheet $sheet, int $row): void
  {
    // Columna R: Tiene Anticipo
    $tieneAnticipoValue = $sheet->getCell("R{$row}")->getValue();
    if ($tieneAnticipoValue === 'SI') {
      $this->applyCellColor($sheet, "R{$row}", '28A745', 'FFFFFF');
    } elseif ($tieneAnticipoValue === 'NO') {
      $this->applyCellColor($sheet, "R{$row}", 'DC3545', 'FFFFFF');
    }

    // Columna V: Estado SUNAT
    $estadoSunatValue = $sheet->getCell("V{$row}")->getValue();
    if ($estadoSunatValue === 'SI') {
      $this->applyCellColor($sheet, "V{$row}", '28A745', 'FFFFFF');
    } elseif ($estadoSunatValue === 'NO') {
      $this->applyCellColor($sheet, "V{$row}", 'DC3545', 'FFFFFF');
    }

    // Columna W: Contabilizada
    $contabilizadaValue = $sheet->getCell("W{$row}")->getValue();
    if ($contabilizadaValue === 'SI') {
      $this->applyCellColor($sheet, "W{$row}", '28A745', 'FFFFFF');
    } elseif ($contabilizadaValue === 'NO') {
      $this->applyCellColor($sheet, "W{$row}", 'A9A9A9', '000000');
    }
  }

  /**
   * Aplica color de fondo y texto a una celda
   *
   * @param Worksheet $sheet
   * @param string $cell
   * @param string $bgColor
   * @param string $textColor
   */
  private function applyCellColor(Worksheet $sheet, string $cell, string $bgColor, string $textColor): void
  {
    $sheet->getStyle($cell)->applyFromArray([
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => $bgColor],
      ],
      'font' => [
        'color' => ['rgb' => $textColor],
      ],
    ]);
  }
}