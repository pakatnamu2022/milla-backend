<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollExclusionRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StorePayrollExclusionRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollExclusionService;

class PayrollExclusionController extends Controller
{
    protected PayrollExclusionService $service;

    public function __construct(PayrollExclusionService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollExclusionRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StorePayrollExclusionRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->destroy($id);
            return $this->success(['message' => 'Exclusión eliminada']);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
