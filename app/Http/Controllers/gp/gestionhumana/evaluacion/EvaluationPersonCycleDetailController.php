<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonCycleDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonCycleDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonCycleDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetailService;

class EvaluationPersonCycleDetailController extends Controller
{
    protected EvaluationPersonCycleDetailService $service;

    public function __construct(EvaluationPersonCycleDetailService $service)
    {
        $this->service = $service;
    }

    public function index(IndexEvaluationPersonCycleDetailRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreEvaluationPersonCycleDetailRequest $request)
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

    public function update(UpdateEvaluationPersonCycleDetailRequest $request, $id)
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
            return $this->service->destroy($id);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
