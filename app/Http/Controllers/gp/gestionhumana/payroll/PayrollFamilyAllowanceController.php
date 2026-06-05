<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollFamilyAllowanceRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StoreOrUpdatePayrollFamilyAllowanceRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollFamilyAllowanceService;

class PayrollFamilyAllowanceController extends Controller
{
    protected PayrollFamilyAllowanceService $service;

    public function __construct(PayrollFamilyAllowanceService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollFamilyAllowanceRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function storeOrUpdate(StoreOrUpdatePayrollFamilyAllowanceRequest $request)
    {
        try {
            return $this->success($this->service->storeOrUpdate($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}