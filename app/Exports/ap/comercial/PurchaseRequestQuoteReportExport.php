<?php

namespace App\Exports\ap\comercial;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseRequestQuoteReportExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $data;
  protected string $fechaInicio;
  protected string $fechaFin;

  public function __construct(Collection $data, string $fechaInicio, string $fechaFin)
  {
    $this->data = $data;
    $this->fechaInicio = $fechaInicio;
    $this->fechaFin = $fechaFin;
  }

  public function collection(): Collection
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'FECHA REGISTRO',
      'CORRELATIVO',
      'SEDE',
      'ASESOR',
      'MARCA',
      'VERSIÓN',
      'OBSERVACIONES',
    ];
  }

  public function map($row): array
  {
    return [
      $row->created_at ? Carbon::parse($row->created_at)->format('d/m/Y H:i') : '',
      $row->correlative ?? '',
      $row->sede->abreviatura ?? '',
      $row->opportunity->worker->nombre_completo ?? '',
      $row->apModelsVn->family->brand->name ?? '',
      $row->apModelsVn->version ?? '',
      $row->comment ?? '',
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => [
        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
      ],
    ];
  }

  public function title(): string
  {
    return 'Cotizaciones';
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $lastRow = $sheet->getHighestRow();

        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->getStyle('A1:G' . $lastRow)->applyFromArray([
          'borders' => [
            'allBorders' => [
              'borderStyle' => Border::BORDER_THIN,
              'color'       => ['rgb' => 'D4D4D4'],
            ],
          ],
        ]);

        $sheet->getStyle('A2:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:G1');
        $sheet->setSelectedCells('A1');
      },
    ];
  }
}
