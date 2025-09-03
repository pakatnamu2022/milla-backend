<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
  protected EvaluationService $service;

  public function __construct(EvaluationService $service)
  {
    $this->service = $service;
  }

  public function index(IndexEvaluationRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationRequest $request)
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

  public function update(UpdateEvaluationRequest $request, int $id)
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
