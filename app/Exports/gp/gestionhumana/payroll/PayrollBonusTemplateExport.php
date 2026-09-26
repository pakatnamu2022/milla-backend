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
 * Plantilla en formato matriz para cargar bonificaciones (gh_payroll_bonuses): columna A = DNI,
 * una columna por periodo elegido desde B (cabecera "MM/AAAA"). RRHH completa el DNI y el monto
 * de cada mes que corresponda; las celdas vacías o en cero se omiten al importar. Permite cargar
 * varios periodos en un mismo archivo. Mismo formato que espera PayrollBonusImport.
 */
class PayrollBonusTemplateExport implements FromArray, WithColumnWidths, WithStyles
{
    private const BLANK_ROWS = 20;

    /** @param array<int, array{year:int,month:int}> $periods */
    public function __construct(
        private readonly array $periods,
    ) {
    }

    public function array(): array
    {
        $periodLabels = array_map(
            fn ($p) => sprintf('%02d/%04d', $p['month'], $p['year']),
            $this->periods,
        );

        $rows = [
            array_merge(['BONIFICACIONES'], array_fill(0, count($periodLabels), '')),
            array_merge(['DNI'], $periodLabels),
        ];

        for ($i = 0; $i < self::BLANK_ROWS; $i++) {
            $rows[] = array_merge([null], array_fill(0, count($periodLabels), null));
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 14];
        foreach (range('B', $this->lastColumnLetter()) as $col) {
            $widths[$col] = 12;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->lastColumnLetter();
        $lastRow = self::BLANK_ROWS + 2;

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Mantener el DNI como texto (evita que Excel elimine ceros a la izquierda).
        $sheet->getStyle("A3:A{$lastRow}")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $sheet->getStyle("A3:A{$lastRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        if (count($this->periods) > 0) {
            $sheet->getStyle("B3:{$lastColumn}{$lastRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $sheet->freezePane('B3');

        return [];
    }

    private function lastColumnLetter(): string
    {
        // Los periodos empiezan en B.
        $index = 1 + max(count($this->periods), 1); // 1-based: A=1, B=2, C=3...
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
