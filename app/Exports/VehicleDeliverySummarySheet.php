<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VehicleDeliverySummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
  private $rows = [];
  private int $numMarcas = 0;
  private int $totalRows = 0;

  public function __construct($data)
  {
    $data = collect($data);

    $sedes = $data
      ->map(fn($r) => data_get($r, 'sede.abreviatura') ?? '-')
      ->unique()->sort()->values()->toArray();

    $marcas = $data
      ->map(fn($r) => data_get($r, 'vehicle.model.family.brand.name') ?? '-')
      ->unique()->sort()->values()->toArray();

    $matrix = [];
    foreach ($sedes as $sede) {
      foreach ($marcas as $marca) {
        $matrix[$sede][$marca] = 0;
      }
    }

    foreach ($data as $row) {
      $sede  = data_get($row, 'sede.abreviatura') ?? '-';
      $marca = data_get($row, 'vehicle.model.family.brand.name') ?? '-';
      if (isset($matrix[$sede][$marca])) {
        $matrix[$sede][$marca]++;
      }
    }

    $header = ['SEDE'];
    foreach ($marcas as $marca) {
      $header[] = $marca;
    }
    $header[] = 'TOTAL';
    $this->rows[] = $header;

    $colTotals = array_fill(0, count($marcas), 0);
    $grandTotal = 0;

    foreach ($sedes as $j => $sede) {
      $row      = [$sede];
      $rowTotal = 0;
      foreach ($marcas as $i => $marca) {
        $count = $matrix[$sede][$marca];
        $row[] = $count;
        $colTotals[$i] += $count;
        $rowTotal += $count;
      }
      $row[]  = $rowTotal;
      $grandTotal += $rowTotal;
      $this->rows[] = $row;
    }

    $totalRow = ['TOTAL'];
    foreach ($colTotals as $colTotal) {
      $totalRow[] = $colTotal;
    }
    $totalRow[]     = $grandTotal;
    $this->rows[]   = $totalRow;
    $this->numMarcas = count($marcas);
    $this->totalRows = count($this->rows);
  }

  public function array(): array
  {
    return $this->rows;
  }

  public function title(): string
  {
    return 'Resumen Sede × Marca';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet   = $event->sheet->getDelegate();
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // ── Encabezado (fila 1): fondo azul oscuro, texto blanco, centrado ──
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Columna de sedes (A): fondo azul claro, negrita ──
        $sheet->getStyle("A1:A{$lastRow}")->applyFromArray([
          'font'      => ['bold' => true],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBDEFB']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Celda A1 (esquina): azul oscuro con texto blanco (reaplicar encima de lo anterior) ──
        $sheet->getStyle('A1')->applyFromArray([
          'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
        ]);

        // ── Columna TOTAL (última): fondo naranja medio, texto blanco ──
        $sheet->getStyle("{$lastCol}1:{$lastCol}{$lastRow}")->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EF6C00']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Fila TOTAL (última): fondo naranja oscuro, texto blanco ──
        $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E65100']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Celda gran total (esquina inferior derecha): rojo oscuro ──
        $sheet->getStyle("{$lastCol}{$lastRow}")->applyFromArray([
          'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B71C1C']],
        ]);

        // ── Centrar números en área de datos ──
        if ($lastRow > 2) {
          $dataLastRow = $lastRow - 1;
          $sheet->getStyle("B2:{$lastCol}{$dataLastRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
          ]);
        }

        // ── Bordes para toda la tabla ──
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
          'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BDBDBD']],
          ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->setSelectedCells('A1');
      },
    ];
  }
}
