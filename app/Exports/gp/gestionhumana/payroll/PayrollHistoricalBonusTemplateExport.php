<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla para cargar el histórico mensual de bono/comisión (hoy solo "BONO_CONDUCTOR", ver
 * PayrollBonusTypeSeeder) que PayrollHistoricalBonusImport espera. Mismo formato "largo" que
 * PayrollHistoricalCalculationTemplateExport (una fila por trabajador+mes).
 */
class PayrollHistoricalBonusTemplateExport implements FromArray, WithHeadings, WithStyles
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
            'BONO',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
