<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollWorkTypeSegmentRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollWorkTypeSegmentService;
use Exception;

class PayrollWorkTypeSegmentController extends Controller
{
  protected PayrollWorkTypeSegmentService $service;

  public function __construct(PayrollWorkTypeSegmentService $service)
  {
    $this->service = $service;
  }

  /**
   * Get segments for a work type
   */
  public function index(int $workTypeId)
  {
    try {
      return $this->success($this->service->listByWorkType($workTypeId));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a new segment
   */
  public function store(StorePayrollWorkTypeSegmentRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update a segment
   */
  public function update(StorePayrollWorkTypeSegmentRequest $request, int $id)
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
   * Delete a segment
   */
  public function destroy(int $id)
  {
    try {
      return $this->success($this->service->destroy($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
