<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationMetricRequest;
use App\Http\Requests\UpdateEvaluationMetricRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationMetricService;
use Illuminate\Http\Request;

class EvaluationMetricController extends Controller
{
  protected EvaluationMetricService $service;

  public function __construct(EvaluationMetricService $service)
  {
    $this->service = $service;
  }


  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationMetricRequest $request)
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

  public function update(UpdateEvaluationMetricRequest $request, int $id)
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

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
