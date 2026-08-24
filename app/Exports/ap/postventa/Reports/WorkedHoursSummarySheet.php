<?php

namespace App\Exports\ap\postventa\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkedHoursSummarySheet implements
  FromCollection,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $workedData;

  public function __construct(Collection $workedData)
  {
    $this->workedData = $workedData;
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
        $subtitleRow = 1;
        $headerRow = 2;
        $dataStartRow = 3;
        $dataEndRow = 2 + $this->workedData->count();
        $totalRow = $dataEndRow;

        // Estilo para subtítulo
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

        // Estilo para encabezado
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

        // Estilo para fila de totales
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

        // Aplicar estilos - Subtítulo
        $sheet->getStyle('A' . $subtitleRow . ':G' . $subtitleRow)->applyFromArray($subtitleStyle);
        $sheet->mergeCells('A' . $subtitleRow . ':G' . $subtitleRow);

        // Aplicar estilos - Encabezado
        $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->applyFromArray($headerStyle);
        $sheet->setAutoFilter('A' . $headerRow . ':G' . $headerRow);

        // Aplicar estilos - Fila de totales
        $sheet->getStyle('A' . $totalRow . ':G' . $totalRow)->applyFromArray($totalRowStyle);

        // Aplicar formato numérico a las columnas de horas (D, E, F, G)
        $sheet->getStyle('D' . $dataStartRow . ':G' . $dataEndRow)
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
