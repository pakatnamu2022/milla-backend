<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexEvaluationCompetenceRequest;
use App\Http\Requests\StoreEvaluationCompetenceRequest;
use App\Http\Requests\UpdateEvaluationCompetenceRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCompetenceService;

class EvaluationCompetenceController extends Controller
{
    protected EvaluationCompetenceService $service;

    public function __construct(EvaluationCompetenceService $service)
    {
        $this->service = $service;
    }

    public function index(IndexEvaluationCompetenceRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }


    public function store(StoreEvaluationCompetenceRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
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

    public function update(UpdateEvaluationCompetenceRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            return $this->success($this->service->destroy($id));
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
