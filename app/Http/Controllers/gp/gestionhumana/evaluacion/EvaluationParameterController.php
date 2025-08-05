<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationParameterRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationParameterRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationParameterRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationParameterService;

class EvaluationParameterController extends Controller
{
    protected EvaluationParameterService $service;

    public function __construct(EvaluationParameterService $service)
    {
        $this->service = $service;
    }

    public function index(IndexEvaluationParameterRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreEvaluationParameterRequest $request)
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

    public function update(UpdateEvaluationParameterRequest $request, $id)
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
