<?php

namespace App\Http\Controllers\ap\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\maestroGeneral\IndexHeaderWarehouseRequest;
use App\Http\Requests\ap\maestroGeneral\StoreHeaderWarehouseRequest;
use App\Http\Requests\ap\maestroGeneral\UpdateHeaderWarehouseRequest;
use App\Http\Services\ap\maestroGeneral\HeaderWarehouseService;

class HeaderWarehouseController extends Controller
{
  protected HeaderWarehouseService $service;

  public function __construct(HeaderWarehouseService $service)
  {
    $this->service = $service;
  }

  public function index(IndexHeaderWarehouseRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreHeaderWarehouseRequest $request)
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

  public function update(UpdateHeaderWarehouseRequest $request, $id)
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
