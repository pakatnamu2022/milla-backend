<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla para cargar el histórico de conceptos variables mensuales (horas extra 25%/35%,
 * feriado, DDT, bonif. nocturna) que PayrollHistoricalCalculationImport espera. Ver ese import
 * para el detalle de cómo se procesa cada columna.
 */
class PayrollHistoricalCalculationTemplateExport implements FromArray, WithHeadings, WithStyles
{
    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(private readonly array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'DNI',
            'TRABAJADOR',
            'ANIO',
            'MES',
            'HORAS_EXTRA_25',
            'HORAS_EXTRA_35',
            'FERIADO',
            'DDT',
            'NOCTURNA',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
