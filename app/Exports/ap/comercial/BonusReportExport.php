<?php

namespace App\Exports\ap\comercial;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BonusReportExport implements WithMultipleSheets
{
  protected Collection $groupedData;

  /**
   * @param Collection $groupedData Filas de bonos agrupadas por sede (clave = nombre de sede).
   */
  public function __construct(Collection $groupedData)
  {
    $this->groupedData = $groupedData;
  }

  public function sheets(): array
  {
    if ($this->groupedData->isEmpty()) {
      return [new BonusReportSheet(collect(), 'Sin Datos')];
    }

    $usedTitles = [];

    return $this->groupedData
      ->map(function (Collection $rows, string $sede) use (&$usedTitles) {
        $title = $this->safeSheetTitle($sede, $usedTitles);
        $usedTitles[] = $title;

        return new BonusReportSheet($rows->values(), $title);
      })
      ->values()
      ->all();
  }

  /**
   * Genera un título de hoja válido para Excel (máx. 31 caracteres, sin
   * caracteres reservados) y único dentro del libro.
   */
  private function safeSheetTitle(string $name, array $usedTitles): string
  {
    $clean = trim(preg_replace('/[\[\]\*\/\\\\\?:]/', '', $name));
    $clean = $clean !== '' ? mb_substr($clean, 0, 31) : 'SIN SEDE';

    $original = $clean;
    $suffix = 1;
    while (in_array($clean, $usedTitles, true)) {
      $suffixStr = ' (' . $suffix . ')';
      $clean = mb_substr($original, 0, 31 - mb_strlen($suffixStr)) . $suffixStr;
      $suffix++;
    }

    return $clean;
  }
}

/**
 * Hoja de detalle de bonos de una sede. Cada fila repite todos los datos de
 * la cotización (N° Cotización, Cliente, Precio, VIN, Factura, Total Bonos)
 * aunque la cotización tenga varios bonos — no se combinan celdas. Para
 * distinguir visualmente cada cotización, se dibuja un borde grueso que
 * "secciona" el bloque de filas de cada cotización.
 */
class BonusReportSheet implements
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
  /** Suma de bonos por N° de cotización, para la columna "TOTAL BONOS". */
  protected Collection $totalsByCorrelative;

  /**
   * Alineación horizontal por columna.
   */
  private const COLUMN_ALIGNMENT = [
    'A' => Alignment::HORIZONTAL_CENTER, // N° Cotización
    'B' => Alignment::HORIZONTAL_LEFT,   // Cliente
    'C' => Alignment::HORIZONTAL_RIGHT,  // Precio Cotización (formato contable)
    'D' => Alignment::HORIZONTAL_CENTER, // VIN
    'E' => Alignment::HORIZONTAL_LEFT,   // Tipo de Bono
    'F' => Alignment::HORIZONTAL_LEFT,   // Concepto del Bono
    'G' => Alignment::HORIZONTAL_RIGHT,  // Monto del Bono (formato contable)
    'H' => Alignment::HORIZONTAL_CENTER, // N° Factura
    'I' => Alignment::HORIZONTAL_RIGHT,  // Monto Factura (formato contable)
    'J' => Alignment::HORIZONTAL_CENTER, // Fecha Factura
    'K' => Alignment::HORIZONTAL_RIGHT,  // Total Bonos Cotización (formato contable)
  ];

  public function __construct(Collection $data, string $title)
  {
    $this->data = $data;
    $this->title = $title;
    $this->totalsByCorrelative = $data
      ->groupBy('correlative')
      ->map(fn(Collection $group) => (float)$group->sum('amount'));
  }

  public function collection(): Collection
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'N° COTIZACIÓN',
      'CLIENTE',
      'PRECIO',
      'VIN',
      'TIPO DE BONO',
      'CONCEPTO DEL BONO',
      'MONTO DEL BONO',
      'N° FACTURA',
      'MONTO FACTURA',
      'FECHA FACTURA',
      'TOTAL BONOS',
    ];
  }

  /**
   * Columnas de moneda: el valor de celda queda como número real (ej. 14900)
   * y se le aplica el formato contable de Excel para que se VEA como
   * "$    14,900.00" ($ pegado a la izquierda, monto alineado a la derecha
   * con relleno de espacios entre ambos), sin tocar el valor almacenado.
   */
  private const CURRENCY_FORMAT = '_("$"* #,##0.00_);_("$"* (#,##0.00);_("$"* "-"??_);_(@_)';
  private const CURRENCY_COLUMNS = ['C', 'G', 'I', 'K'];

  public function map($row): array
  {
    return [
      $row->correlative ?? '',
      $row->client ?? '',
      (float)($row->sale_price ?? 0),
      $row->vin ?? '',
      $row->bonus_type ?? '',
      $row->bonus_concept ?? '',
      (float)($row->amount ?? 0),
      $row->invoice_number ?? '',
      (float)($row->invoice_amount ?? 0),
      $row->invoice_date ?? '',
      $this->totalsByCorrelative->get($row->correlative, 0.0),
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => [
        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
      ],
    ];
  }

  public function title(): string
  {
    return $this->title;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $lastRow = $sheet->getHighestRow();

        $sheet->getRowDimension(1)->setRowHeight(22);

        if ($lastRow >= 2) {
          foreach (self::COLUMN_ALIGNMENT as $col => $horizontal) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getAlignment()->setHorizontal($horizontal);
          }

          foreach (self::CURRENCY_COLUMNS as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
          }
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K1');
        $sheet->setSelectedCells('A1');
      },
    ];
  }

}
