<?php

namespace App\Http\Controllers\ap\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\maestroGeneral\IndexWarehousesByCompanyRequest;
use App\Http\Requests\ap\maestroGeneral\IndexMyWarehouseRequest;
use App\Http\Requests\ap\maestroGeneral\IndexWarehouseRequest;
use App\Http\Requests\ap\maestroGeneral\StoreWarehouseRequest;
use App\Http\Requests\ap\maestroGeneral\UpdateWarehouseRequest;
use App\Http\Services\ap\maestroGeneral\WarehouseService;

class WarehouseController extends Controller
{
  protected WarehouseService $service;

  public function __construct(WarehouseService $service)
  {
    $this->service = $service;
  }

  public function index(IndexWarehouseRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getWarehousesByModelAndSede(IndexMyWarehouseRequest $request)
  {
    try {
      return $this->success($this->service->getWarehousesByModelAndSede($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function getWarehousesByCompany(IndexWarehousesByCompanyRequest $request)
  {
    try {
      return $this->success($this->service->getWarehousesByCompany($request));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreWarehouseRequest $request)
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

  public function update(UpdateWarehouseRequest $request, $id)
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
