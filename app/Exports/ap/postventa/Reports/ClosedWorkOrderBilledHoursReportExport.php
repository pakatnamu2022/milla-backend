<?php

namespace App\Exports\ap\postventa\Reports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClosedWorkOrderBilledHoursReportExport implements WithMultipleSheets
{
  protected array $data;
  protected array $filters;

  public function __construct(array $data, array $filters = [])
  {
    $this->data = $data;
    $this->filters = $filters;
  }

  public function sheets(): array
  {
    $sheets = [
      new ClosedWorkOrderBilledHoursSummarySheet($this->data['summary']),
      new BilledHoursDetailSheet($this->data['detail']),
    ];

    // Agregar hoja de consolidado de OTs
    $sheets[] = new ConsolidadoOTsSheet($this->filters);

    return $sheets;
  }
}
