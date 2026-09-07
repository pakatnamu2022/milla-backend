<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\postventa\repuestos\ApprovedAccessories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestQuoteAdjustmentItem extends Model
{
  protected $table = 'purchase_request_quote_adjustment_items';

  public $timestamps = true;

  protected $fillable = [
    'adjustment_request_id',
    'action',
    'item_type',
    'discount_coupon_id',
    'accessory_detail_id',
    'approved_accessory_id',
    'body_type_id',
    'quantity',
    'additional_price',
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
    'quantity' => 'integer',
    'additional_price' => 'float',
    'previous_valor_unitario' => 'float',
    'new_valor_unitario' => 'float',
    'previous_precio_unitario' => 'float',
    'new_precio_unitario' => 'float',
  ];

  const ACTION_CREATE = 'create';
  const ACTION_UPDATE = 'update';
  const ACTION_DELETE = 'delete';

  const ITEM_TYPE_BONUS_DISCOUNT = 'bonus_discount';
  const ITEM_TYPE_GIFT = 'gift';

  public static function getActions(): array
  {
    return [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE];
  }

  public static function getItemTypes(): array
  {
    return [self::ITEM_TYPE_BONUS_DISCOUNT, self::ITEM_TYPE_GIFT];
  }

  public function adjustmentRequest(): BelongsTo
  {
    return $this->belongsTo(PurchaseRequestQuoteAdjustmentRequest::class, 'adjustment_request_id');
  }

  public function discountCoupon(): BelongsTo
  {
    return $this->belongsTo(DiscountCoupons::class, 'discount_coupon_id');
  }

  public function accessoryDetail(): BelongsTo
  {
    return $this->belongsTo(DetailsApprovedAccessoriesQuote::class, 'accessory_detail_id');
  }

  public function approvedAccessory(): BelongsTo
  {
    return $this->belongsTo(ApprovedAccessories::class, 'approved_accessory_id');
  }

  public function conceptCode(): BelongsTo
  {
    return $this->belongsTo(ApMasters::class, 'concept_code_id');
  }
}
