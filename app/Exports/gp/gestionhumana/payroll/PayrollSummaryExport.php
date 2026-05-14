<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ─────────────────────────────────────────────────────────────
// Main export: 3 hojas
// ─────────────────────────────────────────────────────────────
class PayrollSummaryExport implements WithMultipleSheets
{
  public function __construct(
    private array $attendanceData,
    private array $allDates,
    private array $calcSheet2,
    private array $summaryData,
    private string $periodLabel,
    private ?string $biweeklyLabel,
    private string $companyName
  ) {}

  public function sheets(): array
  {
    return [
      new PayrollAttendanceExcelSheet(
        $this->attendanceData,
        $this->allDates,
        $this->periodLabel,
        $this->biweeklyLabel,
        $this->companyName
      ),
      new PayrollCalcDetailExcelSheet(
        $this->calcSheet2,
        $this->periodLabel,
        $this->biweeklyLabel
      ),
      new PayrollNominaExcelSheet(
        $this->summaryData,
        $this->periodLabel,
        $this->biweeklyLabel,
        $this->companyName
      ),
    ];
  }
}


// ─────────────────────────────────────────────────────────────
// Hoja 1 · Asistencias
// ─────────────────────────────────────────────────────────────
class PayrollAttendanceExcelSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
  private array $attendances;
  private array $allDates;
  private int   $numDays;
  private array $builtRows = [];

  public function __construct(
    private array   $attendanceData,
    array           $allDates,
    private string  $periodLabel,
    private ?string $biweeklyLabel,
    private string  $companyName
  ) {
    $raw = $attendanceData['attendances'] ?? [];
    $this->attendances = is_object($raw) ? $raw->values()->all() : (array) $raw;
    $this->allDates    = $allDates;
    $this->numDays     = count($allDates);
    $this->buildRows();
  }

  private function buildRows(): void
  {
    foreach ($this->attendances as $i => $worker) {
      $map = collect($worker['daily_attendances'])->keyBy('date');
      $row = [$i + 1, $worker['worker_name'], $worker['document_number']];

      foreach ($this->allDates as $date) {
        $att   = $map[$date] ?? null;
        $row[] = $att ? ($att['code'] ?? '') : '';
      }

      $codes   = $worker['summary']['codes'] ?? [];
      $summary = [];
      foreach ($codes as $code => $count) {
        $summary[] = "{$code}: {$count}";
      }
      $row[] = implode("\n", $summary);

      $this->builtRows[] = $row;
    }
  }

  public function array(): array  { return $this->builtRows; }
  public function title(): string { return 'Asistencias'; }

  public function headings(): array
  {
    $heads = ['#', 'Nombre', 'DNI'];
    foreach ($this->allDates as $date) {
      $heads[] = (int)\Carbon\Carbon::parse($date)->format('j');
    }
    $heads[] = 'Resumen';
    return $heads;
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $ws      = $event->sheet->getDelegate();
        $lastRow = $ws->getHighestRow();
        $sumCol  = Coordinate::stringFromColumnIndex(4 + $this->numDays); // Resumen column

        // Header styling
        $ws->getStyle("A1:{$sumCol}1")->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(20);

        // Freeze at column D, row 2 (keeps #/Nombre/DNI visible)
        $ws->freezePane('D2');

        // Column widths
        $ws->getColumnDimension('A')->setWidth(4);
        $ws->getColumnDimension('B')->setWidth(28);
        $ws->getColumnDimension('C')->setWidth(12);
        for ($d = 0; $d < $this->numDays; $d++) {
          $ws->getColumnDimension(Coordinate::stringFromColumnIndex(4 + $d))->setWidth(5);
        }
        $ws->getColumnDimension($sumCol)->setWidth(22);

        // Data rows
        for ($r = 2; $r <= $lastRow; $r++) {
          $workerIdx = $r - 2;
          if (!isset($this->attendances[$workerIdx])) continue;

          $worker = $this->attendances[$workerIdx];
          $map    = collect($worker['daily_attendances'])->keyBy('date');

          // Attendance code cells
          for ($d = 0; $d < $this->numDays; $d++) {
            $col  = Coordinate::stringFromColumnIndex(4 + $d);
            $date = $this->allDates[$d];
            $att  = $map[$date] ?? null;
            $code = $att ? ($att['code'] ?? '') : '';

            $ws->getStyle("{$col}{$r}")->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER)
              ->setVertical(Alignment::VERTICAL_CENTER);
            $ws->getStyle("{$col}{$r}")->getFont()->setSize(7)->setBold((bool) $code);

            if ($code) {
              $rgb = $this->codeColor($code);
              if ($rgb) {
                $ws->getStyle("{$col}{$r}")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB($rgb);
              }
            }
          }

          // Summary column: wrap text, small font
          $ws->getStyle("{$sumCol}{$r}")->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);
          $ws->getStyle("{$sumCol}{$r}")->getFont()->setSize(7);

          // Row height proportional to number of distinct codes
          $codeCount = count($worker['summary']['codes'] ?? []);
          $ws->getRowDimension($r)->setRowHeight(max(14, $codeCount * 10));

          // Alternating row tint for name columns
          if ($workerIdx % 2 === 1) {
            $ws->getStyle("A{$r}:C{$r}")->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('F1F5F9');
          }
        }

        // Borders
        $ws->getStyle("A1:{$sumCol}{$lastRow}")->getBorders()
          ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Name column bold
        $ws->getStyle("B2:B{$lastRow}")->getFont()->setBold(true)->setSize(8);

        // DNI center
        $ws->getStyle("C2:C{$lastRow}")->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
      },
    ];
  }

  private function codeColor(string $code): ?string
  {
    if (str_starts_with($code, 'VC')) return 'D4F1E0';
    if (str_starts_with($code, 'F'))  return 'FDE8E8';
    if ($code === 'N' || str_starts_with($code, 'DN')) return 'DBEAFE';
    if (str_starts_with($code, 'DD')) return 'FEF9C3';
    if ($code === 'D') return 'F0FDF4';
    return null;
  }
}


// ─────────────────────────────────────────────────────────────
// Hoja 2 · Detalles de Cálculo (con filas de detalle colapsadas)
// ─────────────────────────────────────────────────────────────
class PayrollCalcDetailExcelSheet implements WithTitle, WithEvents
{
  private array $rows;
  private float $totalNet;
  private int   $totalWorkers;

  public function __construct(
    private array   $calcSheet2,
    private string  $periodLabel,
    private ?string $biweeklyLabel
  ) {
    $this->rows         = $calcSheet2['rows']->values()->all();
    $this->totalNet     = $calcSheet2['total_net'];
    $this->totalWorkers = $calcSheet2['total_workers'];
  }

  public function title(): string { return 'Detalle Cálculo'; }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $ws = $event->sheet->getDelegate();

        // Summaries above the detail rows
        $ws->setShowSummaryBelow(false);
        $ws->setShowSummaryRight(false);

        // Headers
        $headers = ['Código / Trabajador', 'Categoría / Sueldo', 'Días × Horas / Jornada', 'Multiplicador / Val.H', 'Valor/Hora', 'Monto / Neto'];
        foreach ($headers as $ci => $header) {
          $col = Coordinate::stringFromColumnIndex($ci + 1);
          $ws->setCellValue("{$col}1", $header);
        }
        $ws->getStyle('A1:F1')->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(20);
        $ws->freezePane('A2');

        $currentRow = 2;

        foreach ($this->rows as $worker) {
          // ── Worker summary row ──────────────────────────────
          $ws->setCellValue("A{$currentRow}", $worker['nombre']);
          $ws->setCellValue("B{$currentRow}", $worker['salary']);
          $ws->setCellValue("C{$currentRow}", $worker['shift_hours'] . 'h');
          $ws->setCellValue("D{$currentRow}", $worker['base_hour_value']);
          $ws->setCellValue("F{$currentRow}", $worker['net_salary']);

          $ws->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E3A8A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
          ]);
          $ws->getStyle("B{$currentRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
          $ws->getStyle("D{$currentRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
          $ws->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
          $ws->getStyle("B{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
          $ws->getRowDimension($currentRow)->setRowHeight(16);

          $workerSummaryRow = $currentRow;
          $currentRow++;

          // ── Detail rows (collapsed) ──────────────────────────
          $details = $worker['details'] ?? [];
          $firstDetailRow = $currentRow;

          foreach ($details as $detail) {
            $isDeduction = $detail['type'] === 'DEDUCTION';
            $sign        = $isDeduction ? '-' : '+';
            $daysHours   = $detail['days_worked'] > 0
              ? "{$detail['days_worked']}d × {$detail['hours']}h"
              : "{$detail['hours']}h";

            $ws->setCellValue("A{$currentRow}", $detail['code']);
            $ws->setCellValue("B{$currentRow}", $detail['hour_type'] ?? $detail['category'] ?? '');
            $ws->setCellValue("C{$currentRow}", $daysHours);
            $ws->setCellValue("D{$currentRow}", '×' . number_format($detail['multiplier'], 2));
            $ws->setCellValue("E{$currentRow}", $detail['hour_value']);
            $ws->setCellValue("F{$currentRow}", $detail['amount'] * ($isDeduction ? -1 : 1));

            $ws->getStyle("A{$currentRow}")->getFont()->setBold(true)->setSize(8);
            $ws->getStyle("A{$currentRow}")->getAlignment()->setIndent(2);

            $ws->getStyle("E{$currentRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00"/h"');
            $ws->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode($isDeduction ? '"-S/ "#,##0.00' : '"+S/ "#,##0.00');
            $ws->getStyle("D{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $detailBg = $isDeduction ? 'FEF2F2' : 'F0FDF4';
            $ws->getStyle("A{$currentRow}:F{$currentRow}")->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($detailBg);

            $ws->getStyle("A{$currentRow}:F{$currentRow}")->getFont()->setSize(8)->setColor(
              (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB($isDeduction ? '991B1B' : '166534')
            );

            // Row grouping for collapse
            $ws->getRowDimension($currentRow)->setOutlineLevel(1)->setVisible(false);
            $currentRow++;
          }

          // Mark the first detail row as the collapse trigger
          if ($currentRow > $firstDetailRow) {
            $ws->getRowDimension($firstDetailRow)->setCollapsed(true);
          }

          // Thin border around the whole worker block
          $blockEnd = $currentRow - 1;
          $ws->getStyle("A{$workerSummaryRow}:F{$blockEnd}")->getBorders()
            ->getOutline()->setBorderStyle(Border::BORDER_THIN);

          $currentRow++; // empty spacer row between workers
        }

        // Totals row
        $ws->setCellValue("A{$currentRow}", "Total General ({$this->totalWorkers} trabajador" . ($this->totalWorkers != 1 ? 'es' : '') . ')');
        $ws->setCellValue("F{$currentRow}", $this->totalNet);
        $ws->getStyle("A{$currentRow}:F{$currentRow}")->applyFromArray([
          'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E3A8A']],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
          'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $ws->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
        $ws->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Column widths
        $ws->getColumnDimension('A')->setWidth(28);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(16);
        $ws->getColumnDimension('D')->setWidth(14);
        $ws->getColumnDimension('E')->setWidth(14);
        $ws->getColumnDimension('F')->setWidth(16);
      },
    ];
  }
}


// ─────────────────────────────────────────────────────────────
// Hoja 3 · Resumen de Nómina
// ─────────────────────────────────────────────────────────────
class PayrollNominaExcelSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
  private array $rows;
  private array $totals;
  private bool  $hasVacation;

  public function __construct(
    private array   $summaryData,
    private string  $periodLabel,
    private ?string $biweeklyLabel,
    private string  $companyName
  ) {
    $rowsCollection  = $summaryData['rows'];
    $this->rows      = is_object($rowsCollection) ? $rowsCollection->values()->all() : (array) $rowsCollection;
    $this->totals    = $summaryData['totals'];
    $this->hasVacation = isset($this->rows[0]['days_vacation']);
  }

  public function title(): string { return 'Resumen Nómina'; }

  public function headings(): array
  {
    $h = ['Nombre', 'DNI', 'Días T', 'Básico', 'Bono Noc.', 'Bruto', 'HH.EE 25%', 'HH.EE 35%', 'Feriados', 'Descansos', 'Neto'];
    if ($this->hasVacation) {
      $h[] = 'Días V';
      $h[] = 'Val. D.Vac.';
      $h[] = 'Monto Vac.';
    }
    return $h;
  }

  public function array(): array
  {
    return array_map(function ($row) {
      $r = [
        $row['nombre'],
        $row['dni'],
        $row['days_worked'],
        $row['basic_salary'],
        $row['night_bonus'],
        $row['gross_salary'],
        $row['overtime_25'],
        $row['overtime_35'],
        $row['holiday_pay'],
        $row['compensatory_pay'],
        $row['net_salary'],
      ];
      if ($this->hasVacation) {
        $r[] = $row['days_vacation']        ?? 0;
        $r[] = $row['vacation_hour_value']  ?? 0;
        $r[] = $row['vacation_amount']      ?? 0;
      }
      return $r;
    }, $this->rows);
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $ws      = $event->sheet->getDelegate();
        $lastRow = $ws->getHighestRow();
        $lastCol = $this->hasVacation ? 'N' : 'K';

        // Header
        $ws->getStyle("A1:{$lastCol}1")->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(20);
        $ws->freezePane('C2');
        $ws->setAutoFilter("A1:{$lastCol}1");

        // Money columns (D–K, and optionally M–N)
        $moneyCols = ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        if ($this->hasVacation) {
          $moneyCols[] = 'M';
          $moneyCols[] = 'N';
        }
        foreach ($moneyCols as $col) {
          $ws->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
          $ws->getStyle("{$col}2:{$col}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        // Days columns center
        $ws->getStyle("C2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if ($this->hasVacation) {
          $ws->getStyle("L2:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Alternating rows
        for ($r = 2; $r <= $lastRow; $r++) {
          if ($r % 2 === 1) {
            $ws->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
          }
        }

        // Totals row
        $totalsRow = $lastRow + 2;
        $t = $this->totals;
        $ws->setCellValue("A{$totalsRow}", 'Totales (' . count($this->rows) . ' trabajador' . (count($this->rows) != 1 ? 'es' : '') . ')');
        $ws->setCellValue("C{$totalsRow}", $t['days_worked']);
        $ws->setCellValue("D{$totalsRow}", $t['basic_salary']);
        $ws->setCellValue("E{$totalsRow}", $t['night_bonus']);
        $ws->setCellValue("F{$totalsRow}", $t['gross_salary']);
        $ws->setCellValue("G{$totalsRow}", $t['overtime_25']);
        $ws->setCellValue("H{$totalsRow}", $t['overtime_35']);
        $ws->setCellValue("I{$totalsRow}", $t['holiday_pay']);
        $ws->setCellValue("J{$totalsRow}", $t['compensatory_pay']);
        $ws->setCellValue("K{$totalsRow}", $t['net_salary']);
        if ($this->hasVacation) {
          $ws->setCellValue("L{$totalsRow}", $t['days_vacation']       ?? 0);
          $ws->setCellValue("M{$totalsRow}", $t['vacation_hour_value'] ?? 0);
          $ws->setCellValue("N{$totalsRow}", $t['vacation_amount']     ?? 0);
        }

        $ws->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->applyFromArray([
          'font'    => ['bold' => true, 'color' => ['rgb' => '1E3A8A']],
          'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
          'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM], 'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        foreach ($moneyCols as $col) {
          $ws->getStyle("{$col}{$totalsRow}")->getNumberFormat()->setFormatCode('"S/ "#,##0.00');
          $ws->getStyle("{$col}{$totalsRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Borders
        $ws->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Name bold
        $ws->getStyle("A2:A{$lastRow}")->getFont()->setBold(true);

        // Column widths
        $ws->getColumnDimension('A')->setWidth(30);
        $ws->getColumnDimension('B')->setWidth(11);
        $ws->getColumnDimension('C')->setWidth(7);
        foreach (['D','E','F','G','H','I','J','K'] as $col) {
          $ws->getColumnDimension($col)->setWidth(13);
        }
        if ($this->hasVacation) {
          $ws->getColumnDimension('L')->setWidth(7);
          $ws->getColumnDimension('M')->setWidth(13);
          $ws->getColumnDimension('N')->setWidth(13);
        }
      },
    ];
  }
}
