<?php

namespace App\Exports\ap\postventa\taller;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClosedWorkOrderBilledHoursReportExport implements WithMultipleSheets
{
  protected array $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function sheets(): array
  {
    return [
      new ClosedWorkOrderBilledHoursSummarySheet($this->data['summary']),
      new BilledHoursDetailSheet($this->data['detail']),
    ];
  }
}