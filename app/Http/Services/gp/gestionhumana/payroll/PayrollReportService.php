<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollCalculationExport;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class PayrollReportService
{
  /**
   * Export payroll calculations to Excel
   */
  public function exportToExcel(int $periodId)
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    $calculations = PayrollCalculation::with([
      'worker',
      'company',
      'sede',
      'details'
    ])
      ->where('period_id', $periodId)
      ->orderBy('worker_id')
      ->get();

    if ($calculations->isEmpty()) {
      throw new Exception('No calculations found for this period');
    }

    $filename = "payroll-{$period->code}.xlsx";

    return Excel::download(
      new PayrollCalculationExport($calculations, $period),
      $filename
    );
  }

  /**
   * Get payroll report data for PDF or other formats
   */
  public function getReportData(int $periodId): array
  {
    $period = PayrollPeriod::with(['company'])->find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    $calculations = PayrollCalculation::with([
      'worker',
      'company',
      'sede',
      'details.concept'
    ])
      ->where('period_id', $periodId)
      ->orderBy('worker_id')
      ->get();

    $totals = [
      'total_workers' => $calculations->count(),
      'total_earnings' => $calculations->sum('total_earnings'),
      'total_deductions' => $calculations->sum('total_deductions'),
      'total_net_salary' => $calculations->sum('net_salary'),
      'total_employer_cost' => $calculations->sum('employer_cost'),
    ];

    return [
      'period' => $period,
      'calculations' => $calculations,
      'totals' => $totals,
      'generated_at' => now(),
    ];
  }

  /**
   * Get individual worker payslip data
   */
  public function getPayslipData(int $calculationId): array
  {
    $calculation = PayrollCalculation::with([
      'worker',
      'period',
      'company',
      'sede',
      'details.concept',
      'earnings',
      'deductions',
      'employerContributions'
    ])->find($calculationId);

    if (!$calculation) {
      throw new Exception('Calculation not found');
    }

    return [
      'calculation' => $calculation,
      'worker' => $calculation->worker,
      'period' => $calculation->period,
      'company' => $calculation->company,
      'sede' => $calculation->sede,
      'earnings' => $calculation->earnings,
      'deductions' => $calculation->deductions,
      'employer_contributions' => $calculation->employerContributions,
      'generated_at' => now(),
    ];
  }
}
