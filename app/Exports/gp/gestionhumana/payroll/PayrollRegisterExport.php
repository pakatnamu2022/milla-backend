<?php

namespace App\Exports\gp\gestionhumana\payroll;

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

class PayrollRegisterExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithEvents
{
    protected Collection $registers;
    protected string $periodCode;

    public function __construct(Collection $registers, string $periodCode)
    {
        $this->registers = $registers;
        $this->periodCode = $periodCode;
    }

    public function collection(): Collection
    {
        return $this->registers;
    }

    public function headings(): array
    {
        return [
            // Información General
            'ID',
            'DNI',
            'Trabajador',
            'Centro de Costo',
            'Estado',
            'Cargo',
            'Sueldo Mensual',
            'AFP',
            'Tiene Asig. Familiar',
            'Tiene ESSALUD Vida',

            // Días
            'Días Trabajados',
            'Días Vacaciones',
            'Días Descanso Médico',
            'Días Ausencia',
            'Días Licencia S/Goce',
            'Días Licencia C/Goce',
            'Días Subsidio',
            'Días No Trabajados',
            'Días Efectivos',
            'Horas Normales',

            // Ingresos
            'Sueldo Básico',
            'Asignación Familiar',
            'H. Extra 25%',
            'H. Extra 35%',
            'Subsidio Incapacidad',
            'Cond. Trabajo',
            'Pago Vacaciones',
            'Bono Producción',
            'Pago Feriados',
            'Pago Desc. Trabajados',
            'Bono Nocturno',
            'Bono Comercial',
            'Asig. Escolaridad',
            'Benef. Alimentación',
            'Total Ingresos',

            // BBSS Truncos
            'CTS Trunca',
            'Gratificación',
            'Bono Extraordinario',
            'Vacaciones Truncas',

            // Descuentos
            'ONP',
            'Bono Referencia',
            'AFP Obligatorio',
            'AFP Seguro',
            'AFP Comisión',
            'AFP Total',
            'Renta 5ta',
            'Oncosalud',
            'Adelantos/Préstamos',
            'Otros Descuentos',
            'Desc. Judicial',
            'Monto Gracia',
            'Total Descuentos',

            // Netos
            'Neto Preliminar',
            'Gratif. Navidad',
            'Bono Extraord. Navidad',
            'Aguinaldo',
            'Neto + Aguinaldo',

            // Aportes Empleador
            'CTS Empleador',
            'ESSALUD Empleador',
            'SCTR Total',
            'Seguro Vida',
            'SCTR Salud',
            'SCTR Pensión',
            'Total Aportes Empleador',

            // Netos Finales
            'Vac. Pagadas Prelim.',
            'Neto Final',
            'Total Desc. Trabajador',
        ];
    }

    public function map($register): array
    {
        return [
            // Información General
            $register->id,
            $register->worker_vat,
            $register->worker_name,
            $register->cost_center,
            $register->status,
            $register->occupation,
            number_format($register->monthly_salary, 2),
            $register->afp_affiliation,
            $register->has_family_allowance ? 'Sí' : 'No',
            $register->has_essalud_vida ? 'Sí' : 'No',

            // Días
            number_format($register->days_worked, 1),
            number_format($register->days_vacation, 1),
            number_format($register->days_medical_rest, 1),
            number_format($register->days_absence, 1),
            number_format($register->days_leave_unpaid, 1),
            number_format($register->days_leave_paid, 1),
            number_format($register->days_subsidy, 1),
            number_format($register->days_not_worked, 1),
            number_format($register->days_effective, 1),
            number_format($register->normal_hours, 2),

            // Ingresos
            number_format($register->basic_salary, 2),
            number_format($register->family_allowance, 2),
            number_format($register->overtime_25, 2),
            number_format($register->overtime_35, 2),
            number_format($register->subsidy_disability, 2),
            number_format($register->work_conditions, 2),
            number_format($register->vacation_pay, 2),
            number_format($register->production_bonus, 2),
            number_format($register->holiday_days_pay, 2),
            number_format($register->worked_rest_days_pay, 2),
            number_format($register->night_bonus, 2),
            number_format($register->commercial_bonus, 2),
            number_format($register->schooling_allowance, 2),
            number_format($register->food_benefit, 2),
            number_format($register->total_income, 2),

            // BBSS Truncos
            number_format($register->cts_truncated, 2),
            number_format($register->gratification, 2),
            number_format($register->extraordinary_bonus, 2),
            number_format($register->vacation_truncated, 2),

            // Descuentos
            number_format($register->onp_deduction, 2),
            number_format($register->bonus_referral, 2),
            number_format($register->afp_mandatory, 2),
            number_format($register->afp_insurance, 2),
            number_format($register->afp_commission, 2),
            number_format($register->afp_total, 2),
            number_format($register->income_tax_5th, 2),
            number_format($register->oncosalud_plan, 2),
            number_format($register->advances_loans, 2),
            number_format($register->other_deductions, 2),
            number_format($register->judicial_deductions, 2),
            number_format($register->grace_amount, 2),
            number_format($register->total_deductions, 2),

            // Netos
            number_format($register->net_pay_preliminary, 2),
            number_format($register->christmas_gratification, 2),
            number_format($register->christmas_extraordinary_bonus, 2),
            number_format($register->aguinaldo, 2),
            number_format($register->net_pay_plus_aguinaldo, 2),

            // Aportes Empleador
            number_format($register->cts_employer, 2),
            number_format($register->essalud_employer, 2),
            number_format($register->sctr_total, 2),
            number_format($register->life_insurance, 2),
            number_format($register->sctr_health, 2),
            number_format($register->sctr_pension, 2),
            number_format($register->employer_contributions_total, 2),

            // Netos Finales
            number_format($register->vacation_paid_preliminary, 2),
            number_format($register->net_pay_final, 2),
            number_format($register->worker_deduction_total, 2),
        ];
    }

    public function title(): string
    {
        return 'Registro Planilla ' . $this->periodCode;
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
                    'startColor' => ['rgb' => '1E40AF']
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
                $lastColumn = 'CP';
                $sheet->setAutoFilter("A1:{$lastColumn}1");

                // Header row height
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Alignment for data rows
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setVertical('center');

                // Numeric columns alignment (right)
                $numberColumns = ['G', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
                                 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD',
                                 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM',
                                 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV',
                                 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE',
                                 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN',
                                 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU'];

                foreach ($numberColumns as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getAlignment()->setHorizontal('right');
                }

                // Add totals row
                $totalsRow = $lastRow + 2;
                $sheet->setCellValue("A{$totalsRow}", 'TOTALES');
                $sheet->setCellValue("C{$totalsRow}", $this->registers->count() . ' trabajadores');
                $sheet->getStyle("A{$totalsRow}:C{$totalsRow}")->getFont()->setBold(true);

                // Sum formulas for key numeric columns
                $sumColumns = [
                    'K' => 'Días Trabajados',
                    'L' => 'Días Vacaciones',
                    'M' => 'Días Desc. Médico',
                    'N' => 'Días Ausencia',
                    'S' => 'Días Efectivos',
                    'T' => 'Horas Normales',
                    'U' => 'Sueldo Básico',
                    'V' => 'Asig. Familiar',
                    'W' => 'H. Extra 25%',
                    'X' => 'H. Extra 35%',
                    'AI' => 'Total Ingresos',
                    'BA' => 'Total Descuentos',
                    'BB' => 'Neto Preliminar',
                    'BF' => 'Neto + Aguinaldo',
                    'BM' => 'Total Aportes Empleador',
                    'BO' => 'Neto Final',
                ];

                foreach ($sumColumns as $col => $label) {
                    $sheet->setCellValue("{$col}{$totalsRow}", "=SUM({$col}2:{$col}{$lastRow})");
                }

                $sheet->getStyle("A{$totalsRow}:{$lastColumn}{$totalsRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'EFF6FF']
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