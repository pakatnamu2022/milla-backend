<?php

namespace App\Http\Controllers\ap\postventa\repuestos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\repuestos\IndexApprovedAccessoriesRequest;
use App\Http\Requests\ap\postventa\repuestos\StoreApprovedAccessoriesRequest;
use App\Http\Requests\ap\postventa\repuestos\UpdateApprovedAccessoriesRequest;
use App\Http\Services\ap\postventa\repuestos\ApprovedAccessoriesService;

class ApprovedAccessoriesController extends Controller
{
  protected ApprovedAccessoriesService $service;

  public function __construct(ApprovedAccessoriesService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApprovedAccessoriesRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApprovedAccessoriesRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
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

  public function update(UpdateApprovedAccessoriesRequest $request, $id)
  {
    try {
      $data = $request->all();
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
