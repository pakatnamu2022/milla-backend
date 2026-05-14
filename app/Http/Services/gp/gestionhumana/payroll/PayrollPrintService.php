<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Exports\gp\gestionhumana\payroll\PayrollSummaryExport;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class PayrollPrintService
{
  protected PayrollScheduleService $scheduleService;
  protected PayrollReportService $reportService;

  public function __construct(
    PayrollScheduleService $scheduleService,
    PayrollReportService $reportService
  ) {
    $this->scheduleService = $scheduleService;
    $this->reportService   = $reportService;
  }

  // ── PDF ────────────────────────────────────────────────────

  public function generatePrintPDF(int $periodId, ?int $biweekly)
  {
    [$period, $periodLabel, $biweeklyLabel, $companyName, $attendanceData, $allDates, $calcSheet2, $summaryData]
      = $this->buildSharedData($periodId, $biweekly, withDetails: false);

    $pdf = Pdf::loadView('reports.gp.gestionhumana.payroll.payroll-print', [
      'period'         => $period,
      'periodLabel'    => $periodLabel,
      'biweeklyLabel'  => $biweeklyLabel,
      'companyName'    => $companyName,
      'attendanceData' => $attendanceData,
      'allDates'       => $allDates,
      'calcSheet2'     => $calcSheet2,
      'summaryData'    => $summaryData,
    ]);

    $pdf->setOptions([
      'defaultFont'          => 'Arial',
      'isHtml5ParserEnabled' => true,
      'isRemoteEnabled'      => false,
      'dpi'                  => 96,
    ]);
    $pdf->setPaper('A4', 'landscape');

    $suffix   = $biweekly ? '-q' . $biweekly : '';
    $filename = 'planilla-' . $period->code . $suffix . '.pdf';

    return $pdf->download($filename);
  }

  // ── Excel ──────────────────────────────────────────────────

  public function generateExcel(int $periodId, ?int $biweekly)
  {
    [$period, $periodLabel, $biweeklyLabel, $companyName, $attendanceData, $allDates, $calcSheet2, $summaryData]
      = $this->buildSharedData($periodId, $biweekly, withDetails: true);

    $suffix   = $biweekly ? '-q' . $biweekly : '';
    $filename = 'planilla-' . $period->code . $suffix . '.xlsx';

    return Excel::download(
      new PayrollSummaryExport(
        $attendanceData,
        $allDates,
        $calcSheet2,
        $summaryData,
        $periodLabel,
        $biweeklyLabel,
        $companyName
      ),
      $filename
    );
  }

  // ── Shared data builder ───────────────────────────────────

  private function buildSharedData(int $periodId, ?int $biweekly, bool $withDetails): array
  {
    $period = PayrollPeriod::with(['company'])->find($periodId);
    if (!$period) {
      throw new Exception('Period not found');
    }

    $attendanceData = $this->scheduleService->getAttendancesByPeriod($periodId, $biweekly);

    $startDate = Carbon::parse($attendanceData['start_date']);
    $endDate   = Carbon::parse($attendanceData['end_date']);
    $allDates  = [];
    for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
      $allDates[] = $d->format('Y-m-d');
    }

    $calcSheet2  = $this->getCalculationDetails($period, $periodId, $biweekly, $withDetails);
    $summaryData = $this->reportService->getPayrollReport($periodId, $biweekly);

    $months = [
      1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
      5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
      9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];
    $periodLabel = ($months[$period->month] ?? '') . ' ' . $period->year;

    if ($biweekly === 1 && $period->biweekly_date) {
      $biweeklyLabel = '1ra Quincena · hasta ' . Carbon::parse($period->biweekly_date)->format('d/m');
    } elseif ($biweekly === 2 && $period->biweekly_date) {
      $biweeklyLabel = '2da Quincena · '
        . Carbon::parse($period->biweekly_date)->addDay()->format('d/m')
        . ' – ' . Carbon::parse($period->end_date)->format('d/m');
    } else {
      $biweeklyLabel = null;
    }

    $companyName = $period->company->name ?? 'Empresa';

    return [$period, $periodLabel, $biweeklyLabel, $companyName, $attendanceData, $allDates, $calcSheet2, $summaryData];
  }

  // ── Calculation details query ──────────────────────────────

  private function getCalculationDetails(PayrollPeriod $period, int $periodId, ?int $biweekly, bool $withDetails): array
  {
    $relations = $withDetails ? ['worker', 'details'] : ['worker'];

    $query = PayrollCalculation::with($relations)
      ->join('rrhh_persona as w', 'gh_payroll_calculations.worker_id', '=', 'w.id')
      ->select('gh_payroll_calculations.*')
      ->where('gh_payroll_calculations.period_id', $periodId);

    $sumBoth = ($biweekly === null) && $period->biweekly_date;

    if ($biweekly !== null) {
      $query->where('gh_payroll_calculations.biweekly', $biweekly);
    } elseif ($sumBoth) {
      $query->whereIn('gh_payroll_calculations.biweekly', [1, 2]);
    } else {
      $query->whereNull('gh_payroll_calculations.biweekly');
    }

    $calculations = $query->orderBy('w.nombre_completo')->get();

    if ($sumBoth) {
      $rows = $calculations->groupBy('worker_id')->map(function ($group) use ($withDetails) {
        $first = $group->first();
        $row   = [
          'nombre'          => $first->worker->nombre_completo ?? '-',
          'salary'          => (float) $first->salary,
          'shift_hours'     => (float) $first->shift_hours,
          'base_hour_value' => (float) $first->base_hour_value,
          'net_salary'      => round((float) $group->sum('net_salary'), 2),
        ];
        if ($withDetails) {
          $allDetails = $group->flatMap(fn ($c) => $c->details ?? collect())->values();
          $row['details'] = $this->mapDetails($allDetails);
        }
        return $row;
      })->values();
    } else {
      $rows = $calculations->map(function ($c) use ($withDetails) {
        $row = [
          'nombre'          => $c->worker->nombre_completo ?? '-',
          'salary'          => (float) $c->salary,
          'shift_hours'     => (float) $c->shift_hours,
          'base_hour_value' => (float) $c->base_hour_value,
          'net_salary'      => (float) $c->net_salary,
        ];
        if ($withDetails) {
          $row['details'] = $this->mapDetails($c->details ?? collect());
        }
        return $row;
      });
    }

    return [
      'rows'          => $rows,
      'total_net'     => round($rows->sum('net_salary'), 2),
      'total_workers' => $rows->count(),
    ];
  }

  private function mapDetails($details): array
  {
    return $details->map(fn ($d) => [
      'code'        => $d->concept_code ?? '',
      'category'    => $d->category     ?? '',
      'hour_type'   => $d->hour_type    ?? '',
      'days_worked' => (int)   $d->days_worked,
      'hours'       => (float) $d->hours,
      'multiplier'  => (float) $d->multiplier,
      'hour_value'  => (float) $d->hour_value,
      'amount'      => (float) $d->amount,
      'type'        => $d->type ?? 'EARNING',
    ])->all();
  }
}
