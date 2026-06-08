<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApOrderQuotationDetailsRequest;
use App\Http\Requests\ap\postventa\taller\StoreApOrderQuotationDetailsRequest;
use App\Http\Requests\ap\postventa\taller\UpdateApOrderQuotationDetailsRequest;
use App\Http\Services\ap\postventa\taller\ApOrderQuotationDetailsService;
use App\Http\Utils\Constants;

class ApOrderQuotationDetailsController extends Controller
{
  protected ApOrderQuotationDetailsService $service;

  public function __construct(ApOrderQuotationDetailsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApOrderQuotationDetailsRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApOrderQuotationDetailsRequest $request)
  {
    try {
      $data = $request->validated();

      // Calcular total_cost, net_amount y tax_amount
      $discountPercentage = $data['discount_percentage'] ?? 0;
      $data['total_cost'] = $data['quantity'] * $data['unit_price'];
      $data['net_amount'] = $data['total_cost'] - ($data['total_cost'] * $discountPercentage / 100);
      $data['tax_amount'] = $data['net_amount'] * (Constants::VAT_TAX / 100);

      return $this->success($this->service->store($data));
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

  public function update(UpdateApOrderQuotationDetailsRequest $request, $id)
  {
    try {
      $data = $request->all();
      $data['id'] = $id;

      // Calcular total_cost, net_amount y tax_amount
      $discountPercentage = $data['discount_percentage'] ?? 0;
      $data['total_cost'] = $data['quantity'] * $data['unit_price'];
      $data['net_amount'] = $data['total_cost'] - ($data['total_cost'] * $discountPercentage / 100);
      $data['tax_amount'] = $data['net_amount'] * (Constants::VAT_TAX / 100);

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
