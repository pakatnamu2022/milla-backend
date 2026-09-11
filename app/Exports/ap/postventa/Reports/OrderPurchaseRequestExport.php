<?php

namespace App\Exports\ap\postventa\Reports;

use App\Http\Utils\Constants;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderPurchaseRequestExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected ApOrderPurchaseRequests $purchaseRequest;
  protected array $data;

  public function __construct(int $id)
  {
    $this->purchaseRequest = ApOrderPurchaseRequests::with([
      'apOrderQuotation.client',
      'apOrderQuotation.vehicle.model.family.brand',
      'warehouse',
      'requestedBy.person',
      'details.product',
      'typeCurrency'
    ])->findOrFail($id);

    $this->prepareData();
  }

  /**
   * Preparar los datos para el Excel
   */
  private function prepareData(): void
  {
    $quotation = $this->purchaseRequest->apOrderQuotation;
    $hasQuotation = $quotation !== null;

    $this->data = [
      'request_number' => $this->purchaseRequest->request_number,
      'requested_date' => $this->purchaseRequest->requested_date ?? $this->purchaseRequest->created_at,
      'quotation_number' => $hasQuotation ? $quotation->quotation_number : '-',
      'warehouse_name' => $this->purchaseRequest->warehouse
        ? $this->purchaseRequest->warehouse->id . ' - ' . $this->purchaseRequest->warehouse->description
        : '-',
      'advisor_name' => $this->purchaseRequest->requestedBy?->person?->nombre_completo
        ?? $this->purchaseRequest->requestedBy?->name
        ?? '-',
      'vehicle_plate' => $hasQuotation && $quotation->vehicle ? $quotation->vehicle->plate : '-',
      'vehicle_model' => $this->getVehicleModel($hasQuotation, $quotation),
      'client_name' => $hasQuotation && $quotation->client ? $quotation->client->full_name : '-',
      'observations' => $this->purchaseRequest->observations ?? '',
      'currency_symbol' => $this->purchaseRequest->typeCurrency?->symbol ?? 'S/',
    ];
  }

  /**
   * Obtener el modelo del vehículo
   */
  private function getVehicleModel(bool $hasQuotation, $quotation): string
  {
    if (!$hasQuotation || !$quotation->vehicle || !$quotation->vehicle->model) {
      return '-';
    }

    $vehicle = $quotation->vehicle;
    $brand = $vehicle->model->family->brand->name ?? '';
    $version = $vehicle->model->version ?? '';

    return trim($brand . ' ' . $version);
  }

  public function collection()
  {
    return collect($this->purchaseRequest->details);
  }

  public function headings(): array
  {
    return [
      'Código',
      'Descripción',
      'Tipo Suministro',
      'Cantidad',
      'Precio Unit.',
      '% Descuento',
      'Importe Total',
      'Notas'
    ];
  }

  public function map($detail): array
  {
    $product = $detail->product;

    return [
      $product?->code ?? '-',
      $product?->name ?? '-',
      $detail->supply_type ?? '-',
      $detail->quantity,
      $detail->unit_price ?? 0,
      $detail->discount_percentage ?? 0,
      $detail->total_amount ?? 0,
      $detail->notes ?? '',
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
    return 'Solicitud de Compra';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Información de la solicitud en las primeras filas
        $sheet->insertNewRowBefore(1, 12);

        // Título
        $sheet->setCellValue('A1', 'SOLICITUD DE COMPRA');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
          'font' => ['bold' => true, 'size' => 16],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Información básica
        $row = 2;
        $sheet->setCellValue("A{$row}", 'N° Solicitud:');
        $sheet->setCellValue("B{$row}", $this->data['request_number']);
        $sheet->setCellValue("E{$row}", 'Fecha:');
        $sheet->setCellValue("F{$row}", Carbon::parse($this->data['requested_date'])->format('d/m/Y'));

        $row++;
        $sheet->setCellValue("A{$row}", 'Almacén:');
        $sheet->setCellValue("B{$row}", $this->data['warehouse_name']);
        $sheet->mergeCells("B{$row}:D{$row}");

        $row++;
        $sheet->setCellValue("A{$row}", 'Solicitante:');
        $sheet->setCellValue("B{$row}", $this->data['advisor_name']);
        $sheet->mergeCells("B{$row}:D{$row}");

        $row++;
        if ($this->data['quotation_number'] !== '-') {
          $sheet->setCellValue("A{$row}", 'N° Cotización:');
          $sheet->setCellValue("B{$row}", $this->data['quotation_number']);
        }

        $row++;
        $sheet->setCellValue("A{$row}", 'Cliente:');
        $sheet->setCellValue("B{$row}", $this->data['client_name']);
        $sheet->mergeCells("B{$row}:D{$row}");

        $row++;
        $sheet->setCellValue("A{$row}", 'Vehículo:');
        $sheet->setCellValue("B{$row}", $this->data['vehicle_model']);
        $sheet->setCellValue("E{$row}", 'Placa:');
        $sheet->setCellValue("F{$row}", $this->data['vehicle_plate']);

        $row++;
        $sheet->setCellValue("A{$row}", 'Observaciones:');
        $sheet->setCellValue("B{$row}", $this->data['observations']);
        $sheet->mergeCells("B{$row}:H{$row}");

        // Estilo para las etiquetas
        for ($i = 2; $i <= 9; $i++) {
          $sheet->getStyle("A{$i}")->getFont()->setBold(true);
          $sheet->getStyle("E{$i}")->getFont()->setBold(true);
        }

        // Congelar la fila de encabezados (fila 13)
        $sheet->freezePane('A14');

        // Auto filtro en los encabezados
        $lastRow = $sheet->getHighestRow();
        $sheet->setAutoFilter("A13:H13");

        // Agregar totales al final
        $lastRow = $sheet->getHighestRow();
        $totalRow = $lastRow + 2;

        $currencySymbol = $this->data['currency_symbol'];

        // Calcular total
        $total = 0;
        foreach ($this->purchaseRequest->details as $detail) {
          $total += $detail->total_amount ?? 0;
        }

        // Subtotal
        $sheet->setCellValue("G{$totalRow}", 'Subtotal:');
        $sheet->setCellValue("H{$totalRow}", $currencySymbol . ' ' . number_format($total, 2));
        $sheet->getStyle("G{$totalRow}:H{$totalRow}")->getFont()->setBold(true);

        // IGV
        $totalRow++;
        $igvRate = Constants::VAT_TAX / 100;
        $igv = $total * $igvRate;
        $sheet->setCellValue("G{$totalRow}", 'IGV 18%:');
        $sheet->setCellValue("H{$totalRow}", $currencySymbol . ' ' . number_format($igv, 2));
        $sheet->getStyle("G{$totalRow}:H{$totalRow}")->getFont()->setBold(true);

        // Total
        $totalRow++;
        $totalWithIgv = $total + $igv;
        $sheet->setCellValue("G{$totalRow}", 'TOTAL:');
        $sheet->setCellValue("H{$totalRow}", $currencySymbol . ' ' . number_format($totalWithIgv, 2));
        $sheet->getStyle("G{$totalRow}:H{$totalRow}")->applyFromArray([
          'font' => ['bold' => true, 'size' => 12],
          'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E7E6E6']
          ]
        ]);

        // Aplicar formato de número a las columnas numéricas
        for ($row = 14; $row <= $lastRow; $row++) {
          $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
          $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }
}