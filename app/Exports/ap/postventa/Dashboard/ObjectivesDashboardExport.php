<?php

namespace App\Exports\ap\postventa\Dashboard;

use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\ExecutiveSummarySheet;
use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\GlobalAreasSummarySheet;
use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\HeadquarterDetailSheet;
use App\Exports\ap\postventa\Dashboard\ObjectivesDashboard\HeadquartersRankingSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export objectives dashboard data to Excel with multiple sheets
 *
 * Sheet structure:
 * 1. Executive Summary - Overall period performance
 * 2. Global Areas Summary - Performance by area (Taller, Mesón, Paso Vehicular)
 * 3. Headquarters Ranking - Ranking of all headquarters with area breakdown
 * 4-N. Headquarter Details - One sheet per headquarter with detailed breakdown
 */
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

    // Sheet 1: Executive Summary
    $sheets[] = new ExecutiveSummarySheet($this->data);

    // Sheet 2: Global Areas Summary (Taller, Mesón, Paso Vehicular)
    if (!empty($this->data['global_areas_summary'])) {
      $sheets[] = new GlobalAreasSummarySheet($this->data);
    }

    // Sheet 3: Headquarters Ranking
    if (!empty($this->data['headquarters_comparison']['ranking'])) {
      $sheets[] = new HeadquartersRankingSheet($this->data);
    }

    // Sheets 4-N: Detail per Headquarter
    if (!empty($this->data['headquarters_detail'])) {
      foreach ($this->data['headquarters_detail'] as $headquarter) {
        $sheets[] = new HeadquarterDetailSheet($headquarter);
      }
    }

    return $sheets;
  }
}
