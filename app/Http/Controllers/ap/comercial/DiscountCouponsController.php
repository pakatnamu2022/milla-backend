<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\comercial\DiscountCouponsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DiscountCouponsController extends Controller
{
  protected DiscountCouponsService $service;

  public function __construct(DiscountCouponsService $service)
  {
    $this->service = $service;
  }

  public function byQuote(int $quoteId): JsonResponse
  {
    try {
      return $this->success($this->service->byQuote($quoteId));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(Request $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($id, $request->all()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
