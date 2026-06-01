<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollInsuranceRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollInsuranceRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollInsuranceRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollInsuranceService;
use Exception;

class PayrollInsuranceController extends Controller
{
    protected PayrollInsuranceService $service;

    public function __construct(PayrollInsuranceService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollInsuranceRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StorePayrollInsuranceRequest $request)
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

    public function update(UpdatePayrollInsuranceRequest $request, int $id)
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
}