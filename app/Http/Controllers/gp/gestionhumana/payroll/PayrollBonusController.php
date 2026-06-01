<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollBonusRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollBonusRequest;
use App\Http\Requests\gp\gestionhumana\payroll\UpdatePayrollBonusRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollBonusService;
use Exception;

class PayrollBonusController extends Controller
{
    protected PayrollBonusService $service;

    public function __construct(PayrollBonusService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollBonusRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StorePayrollBonusRequest $request)
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

    public function update(UpdatePayrollBonusRequest $request, int $id)
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