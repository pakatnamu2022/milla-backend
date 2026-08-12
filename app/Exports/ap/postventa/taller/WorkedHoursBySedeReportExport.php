<?php

namespace App\Exports\ap\postventa\taller;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkedHoursBySedeReportExport implements WithMultipleSheets
{
  protected array $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function sheets(): array
  {
    return [
      new WorkedHoursSummarySheet($this->data['summary'], $this->data['billed']),
      new WorkedHoursDetailSheet($this->data['detail']),
    ];
  }
}