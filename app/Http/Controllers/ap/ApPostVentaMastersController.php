<?php

namespace App\Http\Controllers\ap;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\IndexApPostVentaMastersRequest;
use App\Http\Requests\ap\StoreApPostVentaMastersRequest;
use App\Http\Requests\ap\UpdateApPostVentaMastersRequest;
use App\Http\Services\ap\ApPostVentaMastersService;

class ApPostVentaMastersController extends Controller
{
  protected ApPostVentaMastersService $service;

  public function __construct(ApPostVentaMastersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApPostVentaMastersRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApPostVentaMastersRequest $request)
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

  public function update(UpdateApPostVentaMastersRequest $request, $id)
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
