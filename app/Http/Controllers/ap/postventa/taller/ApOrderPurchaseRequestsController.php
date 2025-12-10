<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApOrderPurchaseRequestsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApOrderPurchaseRequestsRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApOrderPurchaseRequestsRequest;
use App\Http\Services\ap\postventa\taller\ApOrderPurchaseRequestsService;

class ApOrderPurchaseRequestsController extends Controller
{
  protected ApOrderPurchaseRequestsService $service;

  public function __construct(ApOrderPurchaseRequestsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApOrderPurchaseRequestsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApOrderPurchaseRequestsRequest $request)
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

  public function update(UpdateApOrderPurchaseRequestsRequest $request, $id)
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