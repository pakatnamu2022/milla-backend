<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla para cargar el histórico de sueldos (rrhh_contrato) que PayrollHistoricalSalaryImport
 * espera. Trae una fila por trabajador activo con su sueldo ACTUAL como referencia (columna
 * FECHA_VIGENCIA_DESDE vacía) — se espera que se agreguen filas adicionales por cada cambio de
 * sueldo anterior que se quiera registrar (mismo DNI, una fila por fecha de vigencia).
 */
class PayrollHistoricalSalaryTemplateExport implements FromArray, WithHeadings, WithStyles
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
            'FECHA_VIGENCIA_DESDE',
            'SUELDO',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
