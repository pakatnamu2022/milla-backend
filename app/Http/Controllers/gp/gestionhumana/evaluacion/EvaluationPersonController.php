<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationPersonRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationPersonRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationPersonRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationPersonService;

class EvaluationPersonController extends Controller
{
  protected EvaluationPersonService $service;

  public function __construct(EvaluationPersonService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationPersonRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationPersonRequest $request)
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

  public function update(UpdateEvaluationPersonRequest $request, int $id)
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
