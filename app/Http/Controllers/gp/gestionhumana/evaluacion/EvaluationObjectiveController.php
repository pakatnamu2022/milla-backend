<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationObjectiveRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationObjectiveRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationObjectiveRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationObjectiveService;

class EvaluationObjectiveController extends Controller
{

    protected EvaluationObjectiveService $service;

    public function __construct(EvaluationObjectiveService $service)
    {
        $this->service = $service;
    }


    public function index(IndexEvaluationObjectiveRequest $request)
    {
        try {
            return $this->service->list($request);
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    public function store(StoreEvaluationObjectiveRequest $request)
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

    public function update(UpdateEvaluationObjectiveRequest $request, $id)
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
