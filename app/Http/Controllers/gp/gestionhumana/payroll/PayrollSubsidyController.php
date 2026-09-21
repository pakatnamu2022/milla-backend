<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\EstimatePayrollSubsidyRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollSubsidyRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollSubsidyRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollSubsidyRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollSubsidyService;

class PayrollSubsidyController extends Controller
{
    protected PayrollSubsidyService $service;

    public function __construct(PayrollSubsidyService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollSubsidyRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show(int $id)
    {
        try {
            return $this->success($this->service->show($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StorePayrollSubsidyRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdatePayrollSubsidyRequest $request, int $id)
    {
        try {
            return $this->success($this->service->update($id, $request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->destroy($id);
            return $this->success(['message' => 'Subsidio eliminado']);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function estimate(EstimatePayrollSubsidyRequest $request)
    {
        try {
            return $this->success($this->service->estimate($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
