<?php

namespace App\Exports\ap\postventa;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductWarehouseStockExport implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithStyles,
  ShouldAutoSize,
  WithTitle,
  WithEvents
{
  protected Collection $data;
  protected string $title;
  protected array $colorRules;

  public function __construct(Collection $data, string $title = 'Reporte de Inventario', array $colorRules = [])
  {
    $this->data = $data;
    $this->title = $title;
    $this->colorRules = $colorRules;
  }

  public function collection()
  {
    return $this->data;
  }

  public function headings(): array
  {
    return [
      'ESTANTE',
      'CÓDIGO PRODUCTO',
      'NOMBRE PRODUCTO',
      'ALMACÉN',
      'CANTIDAD',
      'EN TRÁNSITO',
      'RESERVADA',
      'DISPONIBLE',
      'STOCK MÍNIMO',
      'STOCK MÁXIMO',
      'ESTADO STOCK',
      'COSTO PROMEDIO',
      'PRECIO VENTA',
      'MONEDA',
      'FECHA ÚLT. MOVIMIENTO',
      'TIPO ÚLT. MOVIMIENTO',
      'NÚM. ÚLT. MOVIMIENTO',
      'USUARIO ÚLT. MOVIMIENTO',
      'FECHA ÚLT. ACTUALIZACIÓN STOCK',
    ];
  }

  public function map($row): array
  {
    return [
      $row['estante'] ?? '-',
      $row['codigo_producto'] ?? 'N/A',
      $row['nombre_producto'] ?? 'N/A',
      $row['almacen'] ?? 'N/A',
      $row['cantidad'] ?? '0.00',
      $row['cantidad_en_transito'] ?? '0.00',
      $row['cantidad_reservada'] ?? '0.00',
      $row['cantidad_disponible'] ?? '0.00',
      $row['stock_minimo'] ?? '0.00',
      $row['stock_maximo'] ?? '0.00',
      $row['estado_stock'] ?? 'N/A',
      $row['costo_promedio'] ?? '0.00',
      $row['precio_venta'] ?? '0.00',
      $row['moneda'] ?? 'N/A',
      $row['ultimo_movimiento_fecha'] ?? 'N/A',
      $row['ultimo_movimiento_tipo'] ?? 'N/A',
      $row['ultimo_movimiento_numero'] ?? 'N/A',
      $row['ultimo_movimiento_usuario'] ?? 'N/A',
      $row['fecha_ultimo_movimiento_stock'] ?? 'N/A',
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      // Estilo del encabezado
      1 => [
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
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
          'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
      ],
    ];
  }

  public function registerEvents(): array
  {
    return [
      AfterSheet::class => function (AfterSheet $event) {
        $sheet = $event->sheet->getDelegate();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Habilitar filtros en la fila de encabezado
        $event->sheet->getDelegate()->setAutoFilter("A1:{$highestColumn}1");

        // Alineación central para todas las columnas de datos
        $sheet->getStyle("A2:{$highestColumn}{$highestRow}")
          ->getAlignment()
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Aplicar reglas de color si existen
        if (!empty($this->colorRules) && isset($this->colorRules['estado_stock']) && $highestRow > 1) {
          $statusColumnIndex = 11; // Columna K (ESTADO STOCK)
          $statusColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($statusColumnIndex);

          foreach ($this->colorRules['estado_stock'] as $value => $color) {
            for ($row = 2; $row <= $highestRow; $row++) {
              $cellValue = $sheet->getCell("{$statusColumn}{$row}")->getValue();
              if ($cellValue === $value) {
                $sheet->getStyle("{$statusColumn}{$row}")->applyFromArray([
                  'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                  ],
                ]);
              }
            }
          }
        }

        $sheet->setSelectedCells('A1');
      },
    ];
  }

  public function title(): string
  {
    return mb_substr($this->title, 0, 31); // Excel sheet title limit
  }
}