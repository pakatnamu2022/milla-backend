<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\AddLifeInsurancePolicyWorkerRequest;
use App\Http\Requests\gp\gestionhumana\payroll\IndexLifeInsurancePolicyRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StoreLifeInsurancePolicyRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdateLifeInsurancePolicyRequest;
use App\Http\Services\gp\gestionhumana\payroll\LifeInsurancePolicyService;

class LifeInsurancePolicyController extends Controller
{
    protected LifeInsurancePolicyService $service;

    public function __construct(LifeInsurancePolicyService $service)
    {
        $this->service = $service;
    }

    public function index(IndexLifeInsurancePolicyRequest $request)
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

    public function store(StoreLifeInsurancePolicyRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateLifeInsurancePolicyRequest $request, int $id)
    {
        try {
            return $this->success($this->service->update($id, $request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function addWorker(AddLifeInsurancePolicyWorkerRequest $request, int $id)
    {
        try {
            return $this->success($this->service->addWorker($id, $request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function recalculate(int $id)
    {
        try {
            return $this->success($this->service->recalculate($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
