<?php

namespace App\Http\Controllers\gp\gestionhumana\personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\personal\IndexSalaryIncreaseRequest;
use App\Http\Requests\gp\gestionhumana\personal\StoreSalaryIncreaseRequest;
use App\Http\Services\gp\gestionhumana\personal\SalaryIncreaseService;

class SalaryIncreaseController extends Controller
{
    protected SalaryIncreaseService $service;

    public function __construct(SalaryIncreaseService $service)
    {
        $this->service = $service;
    }

    public function index(IndexSalaryIncreaseRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreSalaryIncreaseRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
