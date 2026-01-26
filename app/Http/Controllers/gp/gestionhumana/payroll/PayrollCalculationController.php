<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\CalculatePayrollRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollCalculatorService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollReportService;
use Exception;
use Illuminate\Http\Request;

class PayrollCalculationController extends Controller
{
  protected PayrollCalculatorService $calculatorService;
  protected PayrollReportService $reportService;

  public function __construct(
    PayrollCalculatorService $calculatorService,
    PayrollReportService $reportService
  ) {
    $this->calculatorService = $calculatorService;
    $this->reportService = $reportService;
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
}
