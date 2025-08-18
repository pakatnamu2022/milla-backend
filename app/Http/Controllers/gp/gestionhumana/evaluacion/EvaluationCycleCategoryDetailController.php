<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationCycleCategoryDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationCycleCategoryDetailRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationCycleCategoryDetailRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetailService;

class EvaluationCycleCategoryDetailController extends Controller
{
    protected EvaluationCycleCategoryDetailService $service;

    public function __construct(EvaluationCycleCategoryDetailService $service)
    {
        $this->service = $service;
    }

    public function index(IndexEvaluationCycleCategoryDetailRequest $request, int $cycleId)
    {
        try {
            return $this->service->list($request, $cycleId);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function storeMany(StoreEvaluationCycleCategoryDetailRequest $request, int $cycleId)
    {
        try {
            return $this->success($this->service->storeMany($cycleId, $request->validated()));
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

    public function update(UpdateEvaluationCycleCategoryDetailRequest $request, $id)
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
