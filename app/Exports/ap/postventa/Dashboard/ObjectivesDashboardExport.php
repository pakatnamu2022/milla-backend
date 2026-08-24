<?php

namespace App\Exports\ap\postventa\Dashboard;

use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\ExecutiveSummarySheet;
use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\HeadquarterDetailSheet;
use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\HeadquartersRankingSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ObjectivesDashboardExport implements WithMultipleSheets
{
  protected array $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function sheets(): array
  {
    $sheets = [];

    // 1. Executive Summary
    $sheets[] = new ExecutiveSummarySheet($this->data);

    // 2. Headquarters Ranking
    $sheets[] = new HeadquartersRankingSheet($this->data);

    // 3. Detail per Headquarter
    foreach ($this->data['headquarters_detail'] as $headquarter) {
      $sheets[] = new HeadquarterDetailSheet($headquarter);
    }

    return $sheets;
  }
}
