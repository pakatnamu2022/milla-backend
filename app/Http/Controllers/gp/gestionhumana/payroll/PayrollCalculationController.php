<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\CalculatePayrollRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollCalculatorService;
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

  public function __construct(
    PayrollCalculatorService $calculatorService,
    PayrollReportService $reportService,
    PayrollSummaryService $summaryService
  ) {
    $this->calculatorService = $calculatorService;
    $this->reportService = $reportService;
    $this->summaryService = $summaryService;
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
   * Calculate payroll for a period
   */
  public function calculate(CalculatePayrollRequest $request)
  {
    try {
      $result = $this->calculatorService->calculatePayroll($request->validated());
      return $this->success([
        'data' => $result,
        'message' => "Payroll calculated successfully for {$result['calculations_count']} workers"
      ]);
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
   * Approve all calculations for a period
   */
  public function approveAll(Request $request)
  {
    try {
      $periodId = $request->input('period_id');
      if (!$periodId) {
        return $this->error('Period ID is required');
      }

      $result = $this->calculatorService->approveAll($periodId);
      return $this->success([
        'data' => $result,
        'message' => "{$result['approved_count']} calculations approved successfully"
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
  public function report(int $periodId)
  {
    try {
      return $this->success($this->reportService->getPayrollReport($periodId));
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
}
