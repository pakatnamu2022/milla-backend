<?php

namespace App\Exports\ap\compras;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PurchaseOrderReportMultiExport implements WithMultipleSheets
{
  protected Collection $data;
  protected ?string $fechaInicio;
  protected ?string $fechaFin;
  protected array $cuentasPorPagar;

  public function __construct(Collection $data, ?string $fechaInicio, ?string $fechaFin, array $cuentasPorPagar = [])
  {
    $this->data            = $data;
    $this->fechaInicio     = $fechaInicio;
    $this->fechaFin        = $fechaFin;
    $this->cuentasPorPagar = $cuentasPorPagar;
  }

  public function sheets(): array
  {
    return [
      new PurchaseOrderReportExport($this->data, $this->fechaInicio, $this->fechaFin, $this->cuentasPorPagar),
      new PurchaseOrderReportStatusLegendExport(),
    ];
  }
}
