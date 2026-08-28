<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteDiscountResource;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use Exception;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiscountCouponsService
{
  public function byQuote(int $quoteId): AnonymousResourceCollection
  {
    $quote = PurchaseRequestQuote::find($quoteId);
    if (!$quote) {
      throw new Exception('Cotización no encontrada.');
    }

    $coupons = DiscountCoupons::with('conceptCode')
      ->where('purchase_request_quote_id', $quoteId)
      ->whereNull('deleted_at')
      ->get();

    return PurchaseRequestQuoteDiscountResource::collection($coupons);
  }
}
