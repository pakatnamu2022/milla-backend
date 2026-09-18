<?php

namespace App\Exports\ap\comercial;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Libro con:
 *  - "Resumen": tabla dinámica Marca > Familia > Versión x meses (Cuenta de VIN), con filas
 *    agrupables (esquema de Excel). SHOP y SEDE son listas desplegables: cada celda de la
 *    matriz es un CONTAR.SI.CONJUNTO sobre "Detalle", así que al cambiar la lista la tabla
 *    se recalcula sola (igual que filtrar una tabla dinámica).
 *  - "Detalle": una fila por VIN (fuente de las fórmulas; también sirve para auditar).
 *  - "Listas" (oculta): opciones de los desplegables y criterios de filtro.
 *
 * Paleta alineada con los reportes generales (encabezado azul 0D47A1, bordes D4D4D4).
 */
class VehicleSalesMatrixExport implements WithMultipleSheets
{
  public const SHEET_SUMMARY = 'Resumen';
  public const SHEET_DETAIL = 'Detalle';
  public const SHEET_LISTS = 'Listas';
  public const ALL = '(Todas)';

  public function __construct(protected array $report)
  {
  }

  public function sheets(): array
  {
    $detail = collect($this->report['detail']);
    $shops = $detail->pluck('shop')->unique()->sort()->values()->all();
    $sedes = $detail->pluck('sede')->unique()->sort()->values()->all();

    return [
      new VehicleSalesMatrixSheet($this->report, $shops, $sedes),
      new VehicleSalesMatrixDetailSheet($this->report['detail']),
      new VehicleSalesMatrixListsSheet($shops, $sedes),
    ];
  }
}

class VehicleSalesMatrixSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
  private const MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
  private const HEADER_ROW = 5;    // tras una fila en blanco

  // Paleta (misma de los reportes generales)
  private const COLOR_HEADER = '0D47A1';
  private const COLOR_BRAND = '1565C0';
  private const COLOR_FAMILY = 'E3F2FD';
  private const COLOR_BORDER = 'D4D4D4';

  /** @var array<int, array{cells: array, type: string}> */
  private array $lines = [];
  private int $monthCount;
  private int $lastColumn;
  private int $firstDataRow;
  private int $lastDataRow;

  public function __construct(private array $report, private array $shops, private array $sedes)
  {
    $this->monthCount = $report['last_month'];
    $this->lastColumn = $this->monthCount + 2;
    $this->firstDataRow = 2;
    $this->lastDataRow = max(2, count($report['detail']) + 1);
    $this->buildLines();
  }

  private function buildLines(): void
  {
    $filters = $this->report['filters'];
    $all = VehicleSalesMatrixExport::ALL;

    $this->lines[] = ['type' => 'filter', 'cells' => ['SHOP', in_array($filters['shop'], $this->shops, true) ? $filters['shop'] : $all]];
    $this->lines[] = ['type' => 'filter', 'cells' => ['SEDE', in_array($filters['sede'], $this->sedes, true) ? $filters['sede'] : $all]];
    $this->lines[] = ['type' => 'filter', 'cells' => ['AÑO', $this->report['year']]];
    $this->lines[] = ['type' => 'blank', 'cells' => ['']];
    $this->lines[] = [
      'type'  => 'header',
      'cells' => array_merge(
        ['Cuenta de VIN'],
        array_slice(self::MONTHS, 0, $this->monthCount),
        ['Total general']
      ),
    ];

    foreach ($this->report['rows'] as $brand) {
      $this->lines[] = $this->line('brand', $brand['name'], [$brand['name']]);
      foreach ($brand['children'] as $family) {
        $this->lines[] = $this->line('family', $family['name'], [$brand['name'], $family['name']]);
        foreach ($family['children'] as $model) {
          $this->lines[] = $this->line('model', $model['name'], [$brand['name'], $family['name'], $model['name']]);
        }
      }
    }

    $this->lines[] = $this->line('total', 'Total general', []);
  }

  /**
   * Fila de la matriz: etiqueta + una fórmula por mes + suma del año.
   * $path = valores de MARCA, FAMILIA, VERSIÓN que acotan la fila (vacío = todo).
   */
  private function line(string $type, string $label, array $path): array
  {
    $row = count($this->lines) + 1;
    $ranges = ['B', 'C', 'D'];    // columnas MARCA, FAMILIA, VERSIÓN en "Detalle"

    $criteria = [];
    foreach ($path as $i => $value) {
      $criteria[] = $this->range($ranges[$i]) . ',' . $this->literal($value);
    }
    $criteria[] = $this->range('E') . ',' . VehicleSalesMatrixExport::SHEET_LISTS . '!$C$1';    // SHOP
    $criteria[] = $this->range('F') . ',' . VehicleSalesMatrixExport::SHEET_LISTS . '!$C$2';    // SEDE

    $cells = [$label];
    for ($m = 1; $m <= $this->monthCount; $m++) {
      $cells[] = '=COUNTIFS(' . implode(',', $criteria) . ',' . $this->range('J') . ',' . $m . ')';
    }
    $cells[] = '=SUM(B' . $row . ':' . Coordinate::stringFromColumnIndex($this->monthCount + 1) . $row . ')';

    return ['type' => $type, 'cells' => $cells];
  }

  private function range(string $column): string
  {
    return VehicleSalesMatrixExport::SHEET_DETAIL . "!\${$column}\${$this->firstDataRow}:\${$column}\${$this->lastDataRow}";
  }

  /** Texto como criterio de COUNTIFS: sin comodines ni operadores accidentales. */
  private function literal(string $value): string
  {
    $escaped = preg_replace('/([~*?])/', '~$1', $value);
    if (preg_match('/^[=<>]/', $escaped)) {
      $escaped = '=' . $escaped;
    }

    return '"' . str_replace('"', '""', $escaped) . '"';
  }

  public function array(): array
  {
    return array_map(fn($l) => $l['cells'], $this->lines);
  }

  public function title(): string
  {
    return VehicleSalesMatrixExport::SHEET_SUMMARY;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $lastCol = Coordinate::stringFromColumnIndex($this->lastColumn);
        $firstMonthCol = 'B';

        $sheet->setShowSummaryBelow(false);

        foreach ($this->lines as $index => $line) {
          $row = $index + 1;
          $range = "A{$row}:{$lastCol}{$row}";
          $style = $sheet->getStyle($range);

          switch ($line['type']) {
            case 'filter':
              $sheet->getStyle("A{$row}")->getFont()->setBold(true);
              break;

            case 'header':
              $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
              $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_HEADER);
              $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
              $sheet->getStyle("B{$row}:{$lastCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
              $sheet->getRowDimension($row)->setRowHeight(22);
              break;

            case 'brand':
              $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
              $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_BRAND);
              break;

            case 'family':
              $style->getFont()->setBold(true);
              $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_FAMILY);
              $sheet->getStyle("A{$row}")->getAlignment()->setIndent(1);
              $sheet->getRowDimension($row)->setOutlineLevel(1);
              break;

            case 'model':
              $sheet->getStyle("A{$row}")->getAlignment()->setIndent(3);
              $sheet->getRowDimension($row)->setOutlineLevel(2);
              break;

            case 'total':
              $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
              $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_HEADER);
              break;
          }

          if (in_array($line['type'], ['brand', 'family', 'model', 'total'], true)) {
            $numbers = $sheet->getStyle("{$firstMonthCol}{$row}:{$lastCol}{$row}");
            // Los ceros se ocultan, como en una tabla dinámica.
            $numbers->getNumberFormat()->setFormatCode('#,##0;-#,##0;;@');
            $numbers->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
          }
        }

        $lastRow = count($this->lines);
        $headerRow = self::HEADER_ROW;

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);

        // Columna de total general resaltada.
        $sheet->getStyle("{$lastCol}" . ($headerRow + 1) . ":{$lastCol}{$lastRow}")
          ->getFont()->setBold(true);

        $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(46);
        for ($col = 2; $col <= $this->lastColumn; $col++) {
          $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))
            ->setAutoSize(false)
            ->setWidth($col === $this->lastColumn ? 14 : 8);
        }

        $this->styleFilterCells($sheet);

        $sheet->freezePane('B' . ($headerRow + 1));
        $sheet->setShowGridlines(false);
        $sheet->setSelectedCells('B1');
      },
    ];
  }

  /** Celdas SHOP / SEDE como listas desplegables (con "(Todas)" para quitar el filtro). */
  private function styleFilterCells(Worksheet $sheet): void
  {
    $lists = [
      1 => ['column' => 'A', 'count' => count($this->shops) + 1],
      2 => ['column' => 'B', 'count' => count($this->sedes) + 1],
    ];

    foreach ($lists as $row => $list) {
      $sheet->mergeCells("B{$row}:E{$row}");

      $box = $sheet->getStyle("B{$row}:E{$row}");
      $box->getFont()->setBold(true);
      $box->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_FAMILY);
      $box->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::COLOR_BORDER);
      $box->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

      $validation = $sheet->getCell("B{$row}")->getDataValidation();
      $validation->setType(DataValidation::TYPE_LIST);
      $validation->setErrorStyle(DataValidation::STYLE_STOP);
      $validation->setAllowBlank(false);
      $validation->setShowDropDown(true);    // en PhpSpreadsheet true = mostrar la flecha de la lista
      $validation->setShowErrorMessage(true);
      $validation->setErrorTitle('Valor no válido');
      $validation->setError('Elige una opción de la lista.');
      $validation->setFormula1(
        VehicleSalesMatrixExport::SHEET_LISTS . "!\${$list['column']}\$1:\${$list['column']}\${$list['count']}"
      );

      $hint = $sheet->getCell("F{$row}");
      $hint->setValue('◄ Elige una opción de la lista para filtrar la tabla');
      $sheet->getStyle("F{$row}")->getFont()->setItalic(true)->getColor()->setRGB('7A7A7A');
    }
  }
}

class VehicleSalesMatrixDetailSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
  public function __construct(private array $detail)
  {
  }

  public function array(): array
  {
    $rows = [[
      'VIN',
      'MARCA',
      'FAMILIA',
      'VERSIÓN',
      'SHOP',
      'SEDE',
      'COMPROBANTE',
      'FECHA COMPROBANTE',
      'FECHA ATRIBUIDA',
      'MES',
    ]];

    foreach ($this->detail as $item) {
      $rows[] = [
        $item['vin'],
        $item['brand'],
        $item['family'],
        $item['model'],
        $item['shop'],
        $item['sede'],
        $item['invoice'],
        $item['invoice_date'],
        $item['sale_date'],
        $item['month'],
      ];
    }

    return $rows;
  }

  public function title(): string
  {
    return VehicleSalesMatrixExport::SHEET_DETAIL;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        $sheet->getStyle('A1:J1')->applyFromArray([
          'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:J1');
        $sheet->setSelectedCells('A1');
      },
    ];
  }
}

/**
 * Hoja oculta: opciones de las listas desplegables (columnas A y B) y criterios
 * de filtro (C1 = SHOP, C2 = SEDE) que usan las fórmulas de "Resumen".
 */
class VehicleSalesMatrixListsSheet implements FromArray, WithTitle, WithEvents
{
  public function __construct(private array $shops, private array $sedes)
  {
  }

  public function array(): array
  {
    $shops = array_merge([VehicleSalesMatrixExport::ALL], $this->shops);
    $sedes = array_merge([VehicleSalesMatrixExport::ALL], $this->sedes);

    $rows = [];
    for ($i = 0; $i < max(count($shops), count($sedes)); $i++) {
      $rows[] = [$shops[$i] ?? null, $sedes[$i] ?? null, $this->criteria($i)];
    }

    return $rows;
  }

  /** "(Todas)" → comodín; otro valor → texto exacto (sin interpretar comodines). */
  private function criteria(int $row): ?string
  {
    $cell = match ($row) {
      0 => VehicleSalesMatrixExport::SHEET_SUMMARY . '!$B$1',
      1 => VehicleSalesMatrixExport::SHEET_SUMMARY . '!$B$2',
      default => null,
    };

    if ($cell === null) {
      return null;
    }

    $escaped = "SUBSTITUTE(SUBSTITUTE(SUBSTITUTE({$cell},\"~\",\"~~\"),\"*\",\"~*\"),\"?\",\"~?\")";

    return '=IF(' . $cell . '="' . VehicleSalesMatrixExport::ALL . '","*",' . $escaped . ')';
  }

  public function title(): string
  {
    return VehicleSalesMatrixExport::SHEET_LISTS;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
      },
    ];
  }
}
