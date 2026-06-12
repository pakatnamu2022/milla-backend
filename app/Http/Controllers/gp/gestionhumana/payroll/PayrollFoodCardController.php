<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollFoodCardRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StoreOrUpdatePayrollFoodCardRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollFoodCardService;

class PayrollFoodCardController extends Controller
{
    protected PayrollFoodCardService $service;

    public function __construct(PayrollFoodCardService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollFoodCardRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function storeOrUpdate(StoreOrUpdatePayrollFoodCardRequest $request)
    {
        try {
            return $this->success($this->service->storeOrUpdate($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}