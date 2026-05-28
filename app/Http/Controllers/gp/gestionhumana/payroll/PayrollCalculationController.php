<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\CalculatePayrollRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollCalculatorService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollPrintService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollReportService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollSummaryService;
use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use Exception;
use Illuminate\Http\Request;

class PayrollCalculationController extends Controller
{
  protected PayrollCalculatorService $calculatorService;
  protected PayrollReportService $reportService;
  protected PayrollSummaryService $summaryService;
  protected PayrollPrintService $printService;

  public function __construct(
    PayrollCalculatorService $calculatorService,
    PayrollReportService     $reportService,
    PayrollSummaryService    $summaryService,
    PayrollPrintService      $printService
  )
  {
    $this->calculatorService = $calculatorService;
    $this->reportService = $reportService;
    $this->summaryService = $summaryService;
    $this->printService = $printService;
  }

  /**
   * Display a listing of calculations
   */
  public function index(Request $request)
  {
    try {
      return $this->calculatorService->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified calculation with details
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->calculatorService->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Approve a single calculation
   */
  public function approve(int $id)
  {
    try {
      return $this->success([
        'data' => $this->calculatorService->approve($id),
        'message' => 'Calculation approved successfully'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get summary for a period
   */
  public function summary(int $periodId)
  {
    try {
      return $this->success($this->calculatorService->getPeriodSummary($periodId));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Export calculations to Excel
   */
  public function export(Request $request)
  {
    try {
      $periodId = $request->query('period_id');
      if (!$periodId) {
        return $this->error('Period ID is required');
      }

      return $this->reportService->exportToExcel($periodId);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get payroll report for a period
   */
  public function report(Request $request, int $periodId)
  {
    try {
      $biweekly = $request->query('biweekly') ? (int)$request->query('biweekly') : null;
      return $this->success($this->reportService->getPayrollReport($periodId, $biweekly));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get payslip data for a worker
   */
  public function payslip(int $id)
  {
    try {
      return $this->success($this->reportService->getPayslipData($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Recalculate and persist payslip summary for an existing calculation.
   * Useful for calculations created before summary columns were added.
   */
  public function summarize(int $id)
  {
    try {
      $calculation = PayrollCalculation::with('details')->find($id);
      if (!$calculation) {
        return $this->error('Calculation not found');
      }

      $updated = $this->summaryService->persist($calculation);

      return $this->success([
        'data' => $this->summaryService->calculate($updated->load('details')),
        'message' => 'Payslip summary recalculated successfully',
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate a multi-page PDF summary for a payroll period.
   * Page 1 – daily attendance grid
   * Page 2 – calculation details (salary, shift, hourly rate, net)
   * Page 3 – full payroll summary (earnings breakdown + net)
   *
   * Query params:
   *   biweekly=1|2  (optional) – restrict to a specific fortnight
   */
  public function printReport(Request $request, int $periodId)
  {
    try {
      $biweekly = $request->query('biweekly') !== null ? (int)$request->query('biweekly') : null;
      return $this->printService->generatePrintPDF($periodId, $biweekly);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate a 3-sheet Excel workbook for a payroll period.
   * Sheet 1 – daily attendance grid (color coded by shift type)
   * Sheet 2 – calculation details with collapsable line items per worker
   * Sheet 3 – full payroll summary with totals row
   *
   * Query params:
   *   biweekly=1|2  (optional) – restrict to a specific fortnight
   */
  public function exportSummary(Request $request, int $periodId)
  {
    try {
      $biweekly = $request->query('biweekly') !== null ? (int)$request->query('biweekly') : null;
      return $this->printService->generateExcel($periodId, $biweekly);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
