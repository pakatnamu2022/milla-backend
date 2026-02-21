<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexAttendanceRuleRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StoreAttendanceRuleRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdateAttendanceRuleRequest;
use App\Http\Services\gp\gestionhumana\payroll\AttendanceRuleService;
use Exception;

class AttendanceRuleController extends Controller
{
  protected AttendanceRuleService $service;

  public function __construct(AttendanceRuleService $service)
  {
    $this->service = $service;
  }

  public function index(IndexAttendanceRuleRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function store(StoreAttendanceRuleRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateAttendanceRuleRequest $request, int $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  public function codes()
  {
    try {
      return $this->success($this->service->codes());
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}