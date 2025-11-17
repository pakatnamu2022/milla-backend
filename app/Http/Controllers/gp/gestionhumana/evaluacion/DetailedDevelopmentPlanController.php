<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexDetailedDevelopmentPlanRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreDetailedDevelopmentPlanRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateDetailedDevelopmentPlanRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\DetailedDevelopmentPlanService;

class DetailedDevelopmentPlanController extends Controller
{

    protected DetailedDevelopmentPlanService $service;

    public function __construct(DetailedDevelopmentPlanService $service)
    {
        $this->service = $service;
    }


    public function index(IndexDetailedDevelopmentPlanRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreDetailedDevelopmentPlanRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            return $this->success($this->service->show($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function update(UpdateDetailedDevelopmentPlanRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            return $this->success($this->service->destroy($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}