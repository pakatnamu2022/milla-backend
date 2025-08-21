<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApBrandGroupsRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApBrandGroupsRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApBrandGroupsRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApBrandGroupsService;
use App\Models\ap\configuracionComercial\vehiculo\ApBrandGroups;
use Illuminate\Http\Request;

class ApBrandGroupsController extends Controller
{
  protected ApBrandGroupsService $service;

  public function __construct(ApBrandGroupsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApBrandGroupsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApBrandGroupsRequest $request)
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

  public function update(UpdateApBrandGroupsRequest $request, $id)
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
