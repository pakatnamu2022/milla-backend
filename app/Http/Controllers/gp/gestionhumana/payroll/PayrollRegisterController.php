<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\ExportPayrollRegisterRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollRegisterRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollRegisterService;
use Illuminate\Http\Request;

class PayrollRegisterController extends Controller
{
  protected PayrollRegisterService $service;

  public function __construct(PayrollRegisterService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPayrollRegisterRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Generar registros de planilla para un periodo
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function generate(Request $request)
  {
    try {
      $request->validate([
        'company_id' => 'required|integer|exists:companies,id',
        'period_id' => 'required|integer|exists:gh_payroll_periods,id',
      ]);

      $result = $this->service->generate(
        $request->input('company_id'),
        $request->input('period_id')
      );

      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Exportar registros de planilla por periodo a Excel
   *
   * @param ExportPayrollRegisterRequest $request
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
   */
  public function export(ExportPayrollRegisterRequest $request)
  {
    try {
      return $this->service->exportByPeriod($request->input('period_id'));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
