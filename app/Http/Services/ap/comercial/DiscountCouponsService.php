<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteDiscountResource;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use Exception;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountCouponsService
{
  public function byQuote(int $quoteId): AnonymousResourceCollection
  {
    $quote = PurchaseRequestQuote::find($quoteId);
    if (!$quote) {
      throw new Exception('Cotización no encontrada.');
    }

    $coupons = DiscountCoupons::where('purchase_request_quote_id', $quoteId)
      ->whereNull('deleted_at')
      ->get();

    return PurchaseRequestQuoteDiscountResource::collection($coupons);
  }

  public function update(int $id, array $data): JsonResource
  {
    $coupon = DiscountCoupons::find($id);
    if (!$coupon) {
      throw new Exception('Cupón no encontrado.');
    }

    $hasRetention = isset($data['has_retention']) ? (bool)$data['has_retention'] : $coupon->has_retention;
    $retentionFactor = $hasRetention ? 0.93 : 1.0;

    $quote = $coupon->purchaseRequestQuote;
    $salePrice = (float)$quote->sale_price;

    if ($coupon->type === 'FIJO') {
      // El valor base es el amount original sin retención
      $baseValue = $coupon->has_retention ? round($coupon->amount / 0.93, 4) : (float)$coupon->amount;
      if (isset($data['value'])) {
        $baseValue = (float)$data['value'];
      }
      $amount = $baseValue * $retentionFactor;
      $percentage = ($salePrice > 0) ? ($amount / $salePrice) * 100 : 0;
      $precioUnitario = $amount;
      $valorUnitario = $precioUnitario / 1.18;
    } else {
      $percentage = isset($data['value']) ? (float)$data['value'] : (float)$coupon->percentage;
      $amount = ($salePrice * $percentage) / 100 * $retentionFactor;
      $precioUnitario = $amount;
      $valorUnitario = $precioUnitario / 1.18;
    }

    $coupon->update([
      'description'   => $data['description'] ?? $coupon->description,
      'percentage'    => $percentage,
      'amount'        => $amount,
      'valor_unitario' => $valorUnitario,
      'precio_unitario' => $precioUnitario,
      'is_negative'   => $data['is_negative'] ?? $coupon->is_negative,
      'has_retention' => $hasRetention,
    ]);

    // Recalcular margen de la cotización
    $quoteService = new PurchaseRequestQuoteService();
    $quoteService->recalculateMargin($quote->id);

    return new PurchaseRequestQuoteDiscountResource($coupon->fresh());
  }
}
