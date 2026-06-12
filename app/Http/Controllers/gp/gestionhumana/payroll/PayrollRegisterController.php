<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexPayrollRegisterRequest;
use App\Http\Services\gp\gestionhumana\payroll\PayrollRegisterService;

class PayrollRegisterController extends Controller
{
    protected PayrollRegisterService $service;

    public function __construct(PayrollRegisterService $service)
    {
        $this->service = $service;
    }

    public function index(IndexPayrollRegisterRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
