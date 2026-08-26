<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestQuoteAdjustmentItem extends Model
{
  protected $table = 'purchase_request_quote_adjustment_items';

  public $timestamps = true;

  protected $fillable = [
    'adjustment_request_id',
    'action',
    'discount_coupon_id',
    'concept_code_id',
    'type',
    'is_negative',
    'has_retention',
    'previous_valor_unitario',
    'new_valor_unitario',
    'previous_precio_unitario',
    'new_precio_unitario',
  ];

  protected $casts = [
    'is_negative' => 'boolean',
    'has_retention' => 'boolean',
    'previous_valor_unitario' => 'float',
    'new_valor_unitario' => 'float',
    'previous_precio_unitario' => 'float',
    'new_precio_unitario' => 'float',
  ];

  const ACTION_CREATE = 'create';
  const ACTION_UPDATE = 'update';
  const ACTION_DELETE = 'delete';

  public static function getActions(): array
  {
    return [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE];
  }

  public function adjustmentRequest(): BelongsTo
  {
    return $this->belongsTo(PurchaseRequestQuoteAdjustmentRequest::class, 'adjustment_request_id');
  }

  public function discountCoupon(): BelongsTo
  {
    return $this->belongsTo(DiscountCoupons::class, 'discount_coupon_id');
  }

  public function conceptCode(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'concept_code_id');
  }
}
