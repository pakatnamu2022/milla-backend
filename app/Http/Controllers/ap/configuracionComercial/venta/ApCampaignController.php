<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\venta\IndexApCampaignRequest;
use App\Http\Requests\ap\configuracionComercial\venta\StoreApCampaignRequest;
use App\Http\Requests\ap\configuracionComercial\venta\UpdateApCampaignRequest;
use App\Http\Services\ap\configuracionComercial\venta\ApCampaignService;

class ApCampaignController extends Controller
{
  protected ApCampaignService $service;

  public function __construct(ApCampaignService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApCampaignRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApCampaignRequest $request)
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

  public function update(UpdateApCampaignRequest $request, $id)
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

  public function active()
  {
    try {
      return $this->success($this->service->active());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
