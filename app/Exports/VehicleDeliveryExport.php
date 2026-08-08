<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VehicleDeliveryExport implements WithMultipleSheets
{
  public function __construct(
    private $data,
    private $columns,
    private $title,
    private $styles,
    private $colorRules
  ) {}

  public function sheets(): array
  {
    return [
      new GeneralExport($this->data, $this->columns, 'Entregas', $this->styles, $this->colorRules),
      new VehicleDeliverySummarySheet($this->data),
    ];
  }
}
