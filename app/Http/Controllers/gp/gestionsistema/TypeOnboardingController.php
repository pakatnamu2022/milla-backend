<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexTypeOnboardingRequest;
use App\Http\Requests\gp\gestionsistema\StoreTypeOnboardingRequest;
use App\Http\Requests\gp\gestionsistema\UpdateTypeOnboardingRequest;
use App\Http\Services\gp\gestionsistema\TypeOnboardingService;

class TypeOnboardingController extends Controller
{
  protected TypeOnboardingService $service;

  public function __construct(TypeOnboardingService $service)
  {
    $this->service = $service;
  }

  public function index(IndexTypeOnboardingRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreTypeOnboardingRequest $request)
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
      return response()->json($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateTypeOnboardingRequest $request, $id)
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
