<?php

namespace App\Exports\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollCalculationExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $calculations;
  protected PayrollPeriod $period;

  public function __construct(Collection $calculations, PayrollPeriod $period)
  {
    $this->calculations = $calculations;
    $this->period = $period;
  }

  public function collection(): Collection
  {
    return $this->calculations;
  }

  public function headings(): array
  {
    return [
      'ID',
      'DNI',
      'Worker Name',
      'Company',
      'Sede',
      'Days Worked',
      'Days Absent',
      'Normal Hours',
      'Extra Hours 25%',
      'Extra Hours 35%',
      'Night Hours',
      'Holiday Hours',
      'Gross Salary',
      'Total Earnings',
      'Total Deductions',
      'Net Salary',
      'Employer Cost',
      'Status',
    ];
  }

  public function map($calculation): array
  {
    return [
      $calculation->id,
      $calculation->worker->vat ?? '',
      $calculation->worker->nombre_completo ?? '',
      $calculation->company->name ?? '',
      $calculation->sede->abreviatura ?? '',
      $calculation->days_worked,
      $calculation->days_absent,
      number_format($calculation->total_normal_hours, 2),
      number_format($calculation->total_extra_hours_25, 2),
      number_format($calculation->total_extra_hours_35, 2),
      number_format($calculation->total_night_hours, 2),
      number_format($calculation->total_holiday_hours, 2),
      number_format($calculation->gross_salary, 2),
      number_format($calculation->total_earnings, 2),
      number_format($calculation->total_deductions, 2),
      number_format($calculation->net_salary, 2),
      number_format($calculation->employer_cost, 2),
      $calculation->status,
    ];
  }

  public function title(): string
  {
    return 'Payroll ' . $this->period->code;
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => [
        'font' => [
          'bold' => true,
          'size' => 11,
          'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
          'fillType' => 'solid',
          'startColor' => ['rgb' => '2E7D32']
        ],
        'alignment' => [
          'horizontal' => 'center',
          'vertical' => 'center'
        ]
      ],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Freeze header row
        $sheet->freezePane('A2');

        // Auto filter
        $lastColumn = 'R';
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        // Header row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Alignment for data rows
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setVertical('center');

        // Number columns alignment (right)
        $numberColumns = ['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
        foreach ($numberColumns as $col) {
          $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getAlignment()->setHorizontal('right');
        }

        // Add totals row
        $totalsRow = $lastRow + 2;
        $sheet->setCellValue("A{$totalsRow}", 'TOTALS');
        $sheet->getStyle("A{$totalsRow}")->getFont()->setBold(true);

        // Sum formulas for numeric columns
        $sumColumns = [
          'F' => 'Days Worked',
          'G' => 'Days Absent',
          'H' => 'Normal Hours',
          'I' => 'Extra 25%',
          'J' => 'Extra 35%',
          'K' => 'Night Hours',
          'L' => 'Holiday Hours',
          'M' => 'Gross Salary',
          'N' => 'Earnings',
          'O' => 'Deductions',
          'P' => 'Net Salary',
          'Q' => 'Employer Cost',
        ];

        foreach ($sumColumns as $col => $label) {
          $sheet->setCellValue("{$col}{$totalsRow}", "=SUM({$col}2:{$col}{$lastRow})");
        }

        $sheet->getStyle("A{$totalsRow}:{$lastColumn}{$totalsRow}")->applyFromArray([
          'font' => ['bold' => true],
          'fill' => [
            'fillType' => 'solid',
            'startColor' => ['rgb' => 'E8F5E9']
          ],
          'borders' => [
            'top' => ['borderStyle' => 'medium'],
            'bottom' => ['borderStyle' => 'medium']
          ]
        ]);
      },
    ];
  }
}
