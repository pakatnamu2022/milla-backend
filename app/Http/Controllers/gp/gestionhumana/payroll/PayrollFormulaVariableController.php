<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollFormulaVariableRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollFormulaVariableRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollFormulaVariableService;
use Exception;
use Illuminate\Http\Request;

class PayrollFormulaVariableController extends Controller
{
  protected PayrollFormulaVariableService $service;

  public function __construct(PayrollFormulaVariableService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of formula variables
   */
  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created formula variable
   */
  public function store(StorePayrollFormulaVariableRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified formula variable
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update the specified formula variable
   */
  public function update(UpdatePayrollFormulaVariableRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Remove the specified formula variable
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
