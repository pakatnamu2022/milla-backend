<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollConceptRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollConceptRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollConceptService;
use Exception;
use Illuminate\Http\Request;

class PayrollConceptController extends Controller
{
  protected PayrollConceptService $service;

  public function __construct(PayrollConceptService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of concepts
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
   * Store a newly created concept
   */
  public function store(StorePayrollConceptRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified concept
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
   * Update the specified concept
   */
  public function update(UpdatePayrollConceptRequest $request, int $id)
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
   * Remove the specified concept
   */
  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Test a concept's formula
   */
  public function testFormula(Request $request, int $id)
  {
    try {
      $testVariables = $request->input('variables', []);
      $result = $this->service->testFormula($id, $testVariables);
      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
