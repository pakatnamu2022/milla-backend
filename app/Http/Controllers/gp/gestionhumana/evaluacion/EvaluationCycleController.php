<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\evaluacion\IndexEvaluationCycleRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\StoreEvaluationCycleRequest;
use App\Http\Requests\gp\gestionhumana\evaluacion\UpdateEvaluationCycleRequest;
use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCycleService;
use Illuminate\Http\Request;

class EvaluationCycleController extends Controller
{
  protected EvaluationCycleService $service;

  public function __construct(EvaluationCycleService $service)
  {
    $this->service = $service;
  }


  public function index(IndexEvaluationCycleRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function participants(int $id)
  {
    try {
      return $this->success($this->service->participants($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function positions(int $id)
  {
    try {
      return $this->success($this->service->positions($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function categories(int $id)
  {
    try {
      return $this->success($this->service->categories($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreEvaluationCycleRequest $request)
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

  public function update(UpdateEvaluationCycleRequest $request, int $id)
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

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
