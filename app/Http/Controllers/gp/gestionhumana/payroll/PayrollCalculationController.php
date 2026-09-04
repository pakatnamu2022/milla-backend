<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\CalculatePayrollRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollCalculatorService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollHistoricalBonusService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollHistoricalCalculationService;
use App\Http\Services\gp\gestionhumana\payroll\PayrollHistoricalSalaryService;
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
  protected PayrollHistoricalCalculationService $historicalService;
  protected PayrollHistoricalBonusService $historicalBonusService;
  protected PayrollHistoricalSalaryService $historicalSalaryService;

  public function __construct(
    PayrollCalculatorService $calculatorService,
    PayrollReportService     $reportService,
    PayrollSummaryService    $summaryService,
    PayrollPrintService      $printService,
    PayrollHistoricalCalculationService $historicalService,
    PayrollHistoricalBonusService $historicalBonusService,
    PayrollHistoricalSalaryService $historicalSalaryService
  )
  {
    $this->calculatorService = $calculatorService;
    $this->reportService = $reportService;
    $this->summaryService = $summaryService;
    $this->printService = $printService;
    $this->historicalService = $historicalService;
    $this->historicalBonusService = $historicalBonusService;
    $this->historicalSalaryService = $historicalSalaryService;
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

  /**
   * Descarga la plantilla para cargar el histórico de conceptos variables mensuales (horas
   * extra, feriado, DDT, bonif. nocturna) de meses anteriores a la existencia del sistema.
   * Query params: company_id, periods[] (cada uno "YYYY-MM", ej. periods[]=2025-06&periods[]=2025-07)
   */
  public function historicalTemplate(Request $request)
  {
    try {
      $companyId = (int) $request->query('company_id');
      if (!$companyId) {
        return $this->error('company_id es requerido');
      }

      $periods = array_map(function ($code) {
        [$year, $month] = explode('-', $code);
        return ['year' => (int) $year, 'month' => (int) $month];
      }, (array) $request->query('periods', []));

      return $this->historicalService->downloadTemplate($companyId, $periods);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Sube el Excel con el histórico de conceptos variables mensuales (mismo formato que
   * historicalTemplate) y lo registra en gh_payroll_calculations, creando el periodo si no existe.
   */
  public function historicalImport(Request $request)
  {
    try {
      $request->validate([
        'company_id' => 'required|integer',
        'file' => 'required|file|mimes:xlsx,xls',
      ]);

      $result = $this->historicalService->import($request->file('file'), (int) $request->input('company_id'));

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Descarga la plantilla para cargar el histórico mensual de bono/comisión (BONO_CONDUCTOR).
   * Query params: company_id, periods[] (cada uno "YYYY-MM").
   */
  public function historicalBonusTemplate(Request $request)
  {
    try {
      $companyId = (int) $request->query('company_id');
      if (!$companyId) {
        return $this->error('company_id es requerido');
      }

      $periods = array_map(function ($code) {
        [$year, $month] = explode('-', $code);
        return ['year' => (int) $year, 'month' => (int) $month];
      }, (array) $request->query('periods', []));

      return $this->historicalBonusService->downloadTemplate($companyId, $periods);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Sube el Excel con el histórico mensual de bono/comisión (mismo formato que
   * historicalBonusTemplate) y lo registra en gh_payroll_bonuses.
   */
  public function historicalBonusImport(Request $request)
  {
    try {
      $request->validate([
        'company_id' => 'required|integer',
        'file' => 'required|file|mimes:xlsx,xls',
      ]);

      $result = $this->historicalBonusService->import($request->file('file'), (int) $request->input('company_id'));

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Descarga la plantilla para cargar el histórico de sueldos (rrhh_contrato).
   * Query params: company_id.
   */
  public function historicalSalaryTemplate(Request $request)
  {
    try {
      $companyId = (int) $request->query('company_id');
      if (!$companyId) {
        return $this->error('company_id es requerido');
      }

      return $this->historicalSalaryService->downloadTemplate($companyId);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Sube el Excel con el histórico de sueldos (mismo formato que historicalSalaryTemplate) y
   * registra/actualiza el historial de contratos (rrhh_contrato) por trabajador.
   */
  public function historicalSalaryImport(Request $request)
  {
    try {
      $request->validate([
        'company_id' => 'required|integer',
        'file' => 'required|file|mimes:xlsx,xls',
      ]);

      $result = $this->historicalSalaryService->import($request->file('file'), (int) $request->input('company_id'));

      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function testPromedio6Meses(Request $request)
  {
    $result = PayrollCalculation::calcularPromedioUltimos6Meses(
      (int) $request->input('period_id'),
      (int) $request->input('worker_id'),
      (int) $request->input('company_id')
    );

    return response()->json($result);
  }
}
