<?php

namespace App\Exports\gp\gestionhumana\payroll;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla para cargar bonificaciones (gh_payroll_bonuses) en el formato que PayrollBonusImport
 * espera: fila 1 título (ignorada), fila 2 cabeceras (columna A = DNI, columna B = MONTO), fila 3
 * en adelante los datos. No viene pre-llenada con todos los trabajadores: RRHH solo agrega el DNI
 * y el monto de quienes realmente tienen el bono asignado ese mes (mismo criterio que
 * PayrollWorkingConditionTemplateExport).
 */
class PayrollBonusTemplateExport implements FromArray, WithColumnWidths, WithStyles
{
    private const BLANK_ROWS = 30;

    public function array(): array
    {
        $rows = [
            ['BONIFICACIONES', ''],
            ['DNI', 'MONTO'],
        ];

        for ($i = 0; $i < self::BLANK_ROWS; $i++) {
            $rows[] = ['', null];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 14,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = self::BLANK_ROWS + 2;

        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $sheet->getStyle("A1:B{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Mantener el DNI como texto (evita que Excel elimine ceros a la izquierda).
        $sheet->getStyle("A3:A{$lastRow}")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $sheet->getStyle("A3:B{$lastRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->freezePane('A3');

        return [];
    }
}
