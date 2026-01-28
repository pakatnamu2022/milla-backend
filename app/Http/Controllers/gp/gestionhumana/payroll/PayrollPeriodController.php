<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollPeriodRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollPeriodRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollPeriodService;
use Exception;
use Illuminate\Http\Request;

class PayrollPeriodController extends Controller
{
  protected PayrollPeriodService $service;

  public function __construct(PayrollPeriodService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of periods
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
   * Store a newly created period
   */
  public function store(StorePayrollPeriodRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified period
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
   * Update the specified period
   */
  public function update(UpdatePayrollPeriodRequest $request, int $id)
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
   * Remove the specified period
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
   * Get the current open period
   */
  public function current(Request $request)
  {
    try {
      $companyId = $request->query('company_id');
      return $this->success($this->service->getCurrentPeriod($companyId));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Close a period
   */
  public function close(int $id)
  {
    try {
      return $this->success([
        'data' => $this->service->closePeriod($id),
        'message' => 'Period closed successfully'
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
