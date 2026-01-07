<?php

namespace App\Http\Controllers\GeneralMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralMaster\IndexGeneralMasterRequest;
use App\Http\Requests\GeneralMaster\StoreGeneralMasterRequest;
use App\Http\Requests\GeneralMaster\UpdateGeneralMasterRequest;
use App\Http\Services\GeneralMaster\GeneralMasterService;

class GeneralMasterController extends Controller
{
  protected GeneralMasterService $service;

  public function __construct(GeneralMasterService $service)
  {
    $this->service = $service;
  }

  public function index(IndexGeneralMasterRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreGeneralMasterRequest $request)
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

  public function update(UpdateGeneralMasterRequest $request, $id)
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

  public function getTypes()
  {
    try {
      return $this->service->getTypes();
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
