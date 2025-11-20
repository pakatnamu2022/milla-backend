<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\compras\IndexPurchaseOrderRequest;
use App\Http\Requests\ap\compras\ResendPurchaseOrderRequest;
use App\Http\Requests\ap\compras\StorePurchaseOrderRequest;
use App\Http\Requests\ap\compras\UpdatePurchaseOrderRequest;
use App\Http\Services\ap\compras\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
  protected PurchaseOrderService $service;

  public function __construct(PurchaseOrderService $service)
  {
    $this->service = $service;
  }

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function index(IndexPurchaseOrderRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StorePurchaseOrderRequest $request)
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

  public function update(UpdatePurchaseOrderRequest $request, $id)
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

  /**
   * Reenvía una OC anulada con datos corregidos
   * Crea nueva OC con punto (.) y la sincroniza a tabla intermedia
   */
  public function resend(ResendPurchaseOrderRequest $request, $id)
  {
    try {
      return $this->success($this->service->resend($request->all(), $id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
