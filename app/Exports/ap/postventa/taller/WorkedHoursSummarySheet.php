<?php

namespace App\Exports\ap\postventa\taller;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WorkedHoursSummarySheet implements
  FromCollection,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $workedData;
  protected Collection $billedData;

  public function __construct(Collection $workedData, Collection $billedData)
  {
    $this->workedData = $workedData;
    $this->billedData = $billedData;
  }

  public function collection()
  {
    $rows = collect();

    // Subtítulo: Horas Trabajadas
    $rows->push(['HORAS TRABAJADAS', '', '', '', '', '', '']);

    // Encabezados para Horas Trabajadas
    $rows->push([
      'SEDE',
      'DNI TÉCNICO',
      'NOMBRE TÉCNICO',
      'HORAS INTERNA',
      'HORAS ESTÁNDAR',
      'HORAS GARANTÍA/RECALL',
      'TOTAL HORAS',
    ]);

    // Datos de Horas Trabajadas
    foreach ($this->workedData as $row) {
      $rows->push([
        $row['sede'],
        $row['dni_tecnico'],
        $row['nombre_tecnico'],
        $row['horas_interna'],
        $row['horas_estandar'],
        $row['horas_garantia_recall'],
        $row['total_horas'],
      ]);
    }

    // Fila vacía de separación
    $rows->push(['', '', '', '', '', '', '']);

    // Subtítulo: Horas Facturadas
    $rows->push(['HORAS FACTURADAS', '', '', '', '', '', '']);

    // Encabezados para Horas Facturadas
    $rows->push([
      'SEDE',
      'DNI TÉCNICO',
      'NOMBRE TÉCNICO',
      'HORAS INTERNA',
      'HORAS ESTÁNDAR',
      'HORAS GARANTÍA/RECALL',
      'TOTAL HORAS',
    ]);

    // Datos de Horas Facturadas
    foreach ($this->billedData as $row) {
      $rows->push([
        $row['sede'],
        $row['dni_tecnico'],
        $row['nombre_tecnico'],
        $row['horas_interna'],
        $row['horas_estandar'],
        $row['horas_garantia_recall'],
        $row['total_horas'],
      ]);
    }

    return $rows;
  }

  public function styles(Worksheet $sheet)
  {
    return [];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();

        // Calcular posiciones de filas
        $workedSubtitleRow = 1;
        $workedHeaderRow = 2;
        $workedDataStartRow = 3;
        $workedDataEndRow = 2 + $this->workedData->count();
        $workedTotalRow = $workedDataEndRow;

        $emptyRow = $workedDataEndRow + 1;

        $billedSubtitleRow = $emptyRow + 1;
        $billedHeaderRow = $billedSubtitleRow + 1;
        $billedDataStartRow = $billedHeaderRow + 1;
        $billedDataEndRow = $billedHeaderRow + $this->billedData->count();
        $billedTotalRow = $billedDataEndRow;

        // Estilo para subtítulos
        $subtitleStyle = [
          'font' => [
            'bold' => true,
            'size' => 14,
            'color' => ['rgb' => 'FFFFFF'],
          ],
          'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2F5496'],
          ],
          'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
          ],
        ];

        // Estilo para encabezados
        $headerStyle = [
          'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11,
          ],
          'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'],
          ],
          'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
          ],
        ];

        // Estilo para filas de totales
        $totalRowStyle = [
          'font' => [
            'bold' => true,
            'size' => 11,
          ],
          'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D3D3D3'],
          ],
          'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_RIGHT,
          ],
        ];

        // Aplicar estilos - Subtítulo Horas Trabajadas
        $sheet->getStyle('A' . $workedSubtitleRow . ':G' . $workedSubtitleRow)->applyFromArray($subtitleStyle);
        $sheet->mergeCells('A' . $workedSubtitleRow . ':G' . $workedSubtitleRow);

        // Aplicar estilos - Encabezado Horas Trabajadas
        $sheet->getStyle('A' . $workedHeaderRow . ':G' . $workedHeaderRow)->applyFromArray($headerStyle);
        $sheet->setAutoFilter('A' . $workedHeaderRow . ':G' . $workedHeaderRow);

        // Aplicar estilos - Fila de totales Horas Trabajadas
        $sheet->getStyle('A' . $workedTotalRow . ':G' . $workedTotalRow)->applyFromArray($totalRowStyle);

        // Aplicar estilos - Subtítulo Horas Facturadas
        $sheet->getStyle('A' . $billedSubtitleRow . ':G' . $billedSubtitleRow)->applyFromArray($subtitleStyle);
        $sheet->mergeCells('A' . $billedSubtitleRow . ':G' . $billedSubtitleRow);

        // Aplicar estilos - Encabezado Horas Facturadas
        $sheet->getStyle('A' . $billedHeaderRow . ':G' . $billedHeaderRow)->applyFromArray($headerStyle);
        $sheet->setAutoFilter('A' . $billedHeaderRow . ':G' . $billedHeaderRow);

        // Aplicar estilos - Fila de totales Horas Facturadas
        $sheet->getStyle('A' . $billedTotalRow . ':G' . $billedTotalRow)->applyFromArray($totalRowStyle);

        // Aplicar formato numérico a las columnas de horas (D, E, F, G) para ambas tablas
        $sheet->getStyle('D' . $workedDataStartRow . ':G' . $workedDataEndRow)
          ->getNumberFormat()
          ->setFormatCode('0.00');

        $sheet->getStyle('D' . $billedDataStartRow . ':G' . $billedDataEndRow)
          ->getNumberFormat()
          ->setFormatCode('0.00');

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return 'Resumen';
  }
}