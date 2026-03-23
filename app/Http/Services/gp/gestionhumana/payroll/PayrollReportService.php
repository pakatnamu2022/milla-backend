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
   * Get payroll report for a period (tabla de boleta por trabajador)
   */
  public function getPayrollReport(int $periodId, ?int $biweekly = null): array
  {
    $period = PayrollPeriod::find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    $query = PayrollCalculation::with(['worker', 'company', 'sede'])
      ->join('rrhh_persona as worker', 'gh_payroll_calculations.worker_id', '=', 'worker.id')
      ->select('gh_payroll_calculations.*')
      ->where('gh_payroll_calculations.period_id', $periodId);

    $sumBothHalves = $biweekly === null && $period->biweekly_date;

    if ($biweekly !== null) {
      $query->where('gh_payroll_calculations.biweekly', $biweekly);
    } elseif ($sumBothHalves) {
      $query->whereIn('gh_payroll_calculations.biweekly', [1, 2]);
    } else {
      $query->whereNull('gh_payroll_calculations.biweekly');
    }

    $calculations = $query->orderBy('worker.nombre_completo')->get();

    if ($sumBothHalves) {
      // Period with biweekly split: sum both halves per worker
      $rows = $calculations->groupBy('worker_id')->map(function ($workerCalcs) {
        $first = $workerCalcs->first();
        return [
          'empresa' => $first->company->name ?? ($first->sede->abreviatura ?? '-'),
          'nombre' => $first->worker->nombre_completo ?? '-',
          'dni' => $first->worker->vat ?? '-',
          'days_worked' => (int)$workerCalcs->sum('days_worked'),
          'basic_salary' => (float)$workerCalcs->sum('basic_salary'),
          'night_bonus' => (float)$workerCalcs->sum('night_bonus'),
          'gross_salary' => (float)$workerCalcs->sum('gross_salary'),
          'overtime_25' => (float)$workerCalcs->sum('overtime_25'),
          'overtime_35' => (float)$workerCalcs->sum('overtime_35'),
          'holiday_pay' => (float)$workerCalcs->sum('holiday_pay'),
          'compensatory_pay' => (float)$workerCalcs->sum('compensatory_pay'),
          'net_salary' => (float)$workerCalcs->sum('net_salary'),
        ];
      })->values();
    } else {
      $rows = $calculations->map(fn($c) => [
        'empresa' => $c->company->name ?? ($c->sede->abreviatura ?? '-'),
        'nombre' => $c->worker->nombre_completo ?? '-',
        'dni' => $c->worker->vat ?? '-',
        'days_worked' => (int)$c->days_worked,
        'basic_salary' => (float)$c->basic_salary,
        'night_bonus' => (float)$c->night_bonus,
        'gross_salary' => (float)$c->gross_salary,
        'overtime_25' => (float)$c->overtime_25,
        'overtime_35' => (float)$c->overtime_35,
        'holiday_pay' => (float)$c->holiday_pay,
        'compensatory_pay' => (float)$c->compensatory_pay,
        'net_salary' => (float)$c->net_salary,
      ]);
    }

    return [
      'period' => [
        'id' => $period->id,
        'code' => $period->code,
        'name' => $period->name,
      ],
      'rows' => $rows,
      'totals' => [
        'days_worked' => $rows->sum('days_worked'),
        'basic_salary' => round($rows->sum('basic_salary'), 2),
        'night_bonus' => round($rows->sum('night_bonus'), 2),
        'gross_salary' => round($rows->sum('gross_salary'), 2),
        'overtime_25' => round($rows->sum('overtime_25'), 2),
        'overtime_35' => round($rows->sum('overtime_35'), 2),
        'holiday_pay' => round($rows->sum('holiday_pay'), 2),
        'compensatory_pay' => round($rows->sum('compensatory_pay'), 2),
        'net_salary' => round($rows->sum('net_salary'), 2),
      ],
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
      'summary' => [
        'days_worked' => (int)$calculation->days_worked,
        'basic_salary' => (float)$calculation->basic_salary,
        'night_bonus' => (float)$calculation->night_bonus,
        'gross_salary' => (float)$calculation->gross_salary,
        'overtime_25' => (float)$calculation->overtime_25,
        'overtime_35' => (float)$calculation->overtime_35,
        'holiday_pay' => (float)$calculation->holiday_pay,
        'compensatory_pay' => (float)$calculation->compensatory_pay,
        'total_deductions' => (float)$calculation->total_deductions,
        'net_salary' => (float)$calculation->net_salary,
      ],
      'generated_at' => now(),
    ];
  }
}
