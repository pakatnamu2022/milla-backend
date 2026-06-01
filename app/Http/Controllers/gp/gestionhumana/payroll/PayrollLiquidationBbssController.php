<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollLiquidationBbssRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollLiquidationBbssRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollLiquidationBbssRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollLiquidationBbssService;
use Exception;

class PayrollLiquidationBbssController extends Controller
{
    protected PayrollLiquidationBbssService $service;

    public function __construct(PayrollLiquidationBbssService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollLiquidationBbssRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StorePayrollLiquidationBbssRequest $request)
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

    public function update(UpdatePayrollLiquidationBbssRequest $request, int $id)
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