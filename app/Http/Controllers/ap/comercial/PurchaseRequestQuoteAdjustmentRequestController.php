<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexPurchaseRequestQuoteAdjustmentRequestRequest;
use App\Http\Requests\ap\comercial\RejectPurchaseRequestQuoteAdjustmentRequestRequest;
use App\Http\Requests\ap\comercial\StorePurchaseRequestQuoteAdjustmentRequestRequest;
use App\Http\Services\ap\comercial\PurchaseRequestQuoteAdjustmentRequestService;

class PurchaseRequestQuoteAdjustmentRequestController extends Controller
{
  protected PurchaseRequestQuoteAdjustmentRequestService $service;

  public function __construct(PurchaseRequestQuoteAdjustmentRequestService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPurchaseRequestQuoteAdjustmentRequestRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StorePurchaseRequestQuoteAdjustmentRequestRequest $request)
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

  public function approve($id)
  {
    try {
      return $this->success($this->service->approve($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function reject(RejectPurchaseRequestQuoteAdjustmentRequestRequest $request, $id)
  {
    try {
      return $this->success($this->service->reject($id, $request->validated()['reason'] ?? null));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      $this->service->destroy((int)$id);
      return response()->json(['message' => 'Solicitud de ajuste cancelada correctamente.']);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
