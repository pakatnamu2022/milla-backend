<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexPurchaseRequestQuoteRequest;
use App\Http\Requests\ap\comercial\StorePurchaseRequestQuoteRequest;
use App\Http\Requests\ap\comercial\UpdatePurchaseRequestQuoteRequest;
use App\Http\Services\ap\comercial\PurchaseRequestQuoteService;

class PurchaseRequestQuoteController extends Controller
{
  protected PurchaseRequestQuoteService $service;

  public function __construct(PurchaseRequestQuoteService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPurchaseRequestQuoteRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StorePurchaseRequestQuoteRequest $request)
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

  public function update(UpdatePurchaseRequestQuoteRequest $request, $id)
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
