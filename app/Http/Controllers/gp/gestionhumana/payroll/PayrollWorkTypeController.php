<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollWorkTypeRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollWorkTypeRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollWorkTypeRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollWorkTypeService;
use Exception;

class PayrollWorkTypeController extends Controller
{
  protected PayrollWorkTypeService $service;

  public function __construct(PayrollWorkTypeService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of work types
   */
  public function index(IndexPayrollWorkTypeRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created work type
   */
  public function store(StorePayrollWorkTypeRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified work type
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
   * Update the specified work type
   */
  public function update(UpdatePayrollWorkTypeRequest $request, int $id)
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
   * Remove the specified work type
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
