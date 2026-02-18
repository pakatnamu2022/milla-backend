<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexDiscountRequestsOrderQuotationRequest;
use App\Http\Requests\ap\postventa\taller\StoreDiscountRequestsOrderQuotationRequest;
use App\Http\Requests\ap\postventa\taller\UpdateDiscountRequestsOrderQuotationRequest;
use App\Http\Services\ap\postventa\taller\DiscountRequestsOrderQuotationService;

class DiscountRequestsOrderQuotationController extends Controller
{
  protected DiscountRequestsOrderQuotationService $service;

  public function __construct(DiscountRequestsOrderQuotationService $service)
  {
    $this->service = $service;
  }

  public function index(IndexDiscountRequestsOrderQuotationRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreDiscountRequestsOrderQuotationRequest $request)
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

  public function update(UpdateDiscountRequestsOrderQuotationRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
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

  public function destroy($id)
  {
    try {
      $this->service->destroy($id);
      return response()->json(['message' => 'Solicitud de descuento eliminada correctamente.']);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}