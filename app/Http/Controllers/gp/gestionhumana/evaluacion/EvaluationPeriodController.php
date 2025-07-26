<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexEvaluationPeriodRequest;
use App\Http\Requests\StoreEvaluationPeriodRequest;
use App\Http\Requests\UpdateEvaluationPeriodRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPeriodService;

class EvaluationPeriodController extends Controller
{
    protected EvaluationPeriodService $service;

    public function __construct(EvaluationPeriodService $service)
    {
        $this->service = $service;
    }

    public function index(IndexEvaluationPeriodRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreEvaluationPeriodRequest $request)
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

    public function update(UpdateEvaluationPeriodRequest $request, $id)
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
