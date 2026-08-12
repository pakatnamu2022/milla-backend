<?php

namespace App\Exports\ap\compras;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PurchaseOrderReportExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $data;
  protected string $fechaInicio;
  protected string $fechaFin;

  // Total de columnas del reporte
  private const LAST_COL = 'R';
  private const TOTAL_COLS = 18;

  public function __construct(Collection $data, string $fechaInicio, string $fechaFin)
  {
    $this->data       = $data;
    $this->fechaInicio = $fechaInicio;
    $this->fechaFin   = $fechaFin;
  }

  public function collection(): Collection
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'NÚMERO OPERACIÓN',
      'NOMBRE IMPORTADOR',
      'FECHA DE EMISIÓN',
      'FECHA DE VENCIMIENTO',
      'IMPORTE INICIAL',
      'NÚMERO FACTURA',
      'NÚMERO VIN',
      'TIPO VEHÍCULO',
      'MARCA VEHÍCULO',
      'MODELO VEHÍCULO',
      'COLOR VEHÍCULO',
      'AÑO FÁBRICA',
      'SERIE MOTOR',
      'SEDE',
      'DIAS DE VENCIDO',
      'RENOVACIONES',
      'PAGADO',
      'ESTATUS',
    ];
  }

  public function map($row): array
  {
    $emisDate    = $row->emission_date ? Carbon::parse($row->emission_date) : null;
    $diasVencido = $emisDate ? (int) $emisDate->diffInDays(Carbon::today(), false) : 0;

    return [
      $row->number ?? '',
      $row->supplier->full_name ?? '',
      $emisDate ? $emisDate->format('d/m/Y') : '',
      $row->due_date ? Carbon::parse($row->due_date)->format('d/m/Y') : '',
      $row->total ?? 0,
      trim(($row->invoice_series ?? '') . '-' . ($row->invoice_number ?? ''), '-'),
      $row->vehicle->vin ?? '',
      $row->vehicle->model->vehicleType->description ?? '',
      $row->vehicle->model->family->brand->name ?? '',
      $row->vehicle->model->version ?? '',
      $row->vehicle->color->description ?? '',
      $row->vehicle->year ?? '',
      $row->vehicle->engine_number ?? '',
      $row->sede->suc_abrev ?? '',
      $diasVencido,
      $this->calcRenovaciones($diasVencido),
      '',
      $this->getEstatus($row),
    ];
  }

  public function title(): string
  {
    return 'Órdenes de Compra';
  }

  public function registerEvents(): array
  {
    $fechaRef = Carbon::today()->format('d/m/Y');

    return [
      AfterSheet::class => function (AfterSheet $event) use ($fechaRef) {
        $sheet   = $event->sheet->getDelegate();
        $lastRow = $sheet->getHighestRow();
        $lastCol = self::LAST_COL;

        // Insertar fila de fecha de referencia antes de los headers
        $sheet->insertNewRowBefore(1, 1);

        // Fecha de referencia en A1
        $sheet->setCellValue('A1', 'FECHA DE REFERENCIA: ' . $fechaRef);
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getStyle('A1')->applyFromArray([
          'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1A237E']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EAF6']],
        ]);

        // Estilo de headers (ahora en fila 2)
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
          'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Bordes para toda la tabla
        $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color'       => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        // Alineación centrada para columnas numéricas y de fecha
        $centeredCols = ['C', 'D', 'E', 'L', 'O', 'P', 'Q', 'R'];
        foreach ($centeredCols as $col) {
          $sheet->getStyle($col . '3:' . $col . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Color de ESTATUS según valor
        for ($i = 3; $i <= $lastRow; $i++) {
          $estatus = $sheet->getCell('R' . $i)->getValue();
          $color   = match ($estatus) {
            'LIBRE'   => ['bg' => '43A047', 'text' => 'FFFFFF'],
            'CONTADO' => ['bg' => '1E88E5', 'text' => 'FFFFFF'],
            'CREDITO' => ['bg' => 'FB8C00', 'text' => 'FFFFFF'],
            default   => null,
          };

          if ($color) {
            $sheet->getStyle('R' . $i)->applyFromArray([
              'font' => ['bold' => true, 'color' => ['rgb' => $color['text']]],
              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['bg']]],
              'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
          }
        }

        $sheet->freezePane('A3');
        $sheet->setAutoFilter('A2:' . $lastCol . '2');
        $sheet->setSelectedCells('A1');
      },
    ];
  }

  private function calcRenovaciones(int $dias): string
  {
    if ($dias <= 0)   return '0';
    if ($dias <= 30)  return '0';
    if ($dias <= 60)  return '1';
    if ($dias <= 90)  return '2';
    if ($dias <= 120) return '3';
    if ($dias <= 150) return '4';
    if ($dias <= 180) return '5';
    if ($dias <= 210) return '6';
    if ($dias <= 240) return '7';
    return '8';
  }

  private function getEstatus($row): string
  {
    $doc = $row->vehicle?->electronicDocumentParent;

    if (!$doc) {
      return 'LIBRE';
    }

    return $doc->financing_type === 'CONTADO' ? 'CONTADO' : 'CREDITO';
  }
}
