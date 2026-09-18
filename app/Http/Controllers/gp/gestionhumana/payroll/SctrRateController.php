<?php

namespace App\Http\Controllers\gp\gestionhumana\payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\payroll\IndexSctrRateRequest;
use App\Http\Requests\gp\gestionhumana\payroll\StoreSctrRateRequest;
use App\Http\Services\gp\gestionhumana\payroll\SctrRateService;

class SctrRateController extends Controller
{
    protected SctrRateService $service;

    public function __construct(SctrRateService $service)
    {
        $this->service = $service;
    }

    public function index(IndexSctrRateRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreSctrRateRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
