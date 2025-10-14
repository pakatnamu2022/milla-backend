<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCoupons extends Model
{
  use SoftDeletes;

  protected $table = 'discount_coupons';

  protected $fillable = [
    'description',
    'type',
    'percentage',
    'amount',
    'concept_code_id',
    'purchase_request_quote_id',
  ];

  const filter = [
    'concept_code_id' => '=',
    'purchase_request_quote_id' => '=',
    'type' => '=',
  ];

  const sorts = [
    'id',
    'created_at',
    'updated_at',
  ];

  public function conceptCode(): BelongsTo
  {
    return $this->belongsTo(ApCommercialMasters::class, 'concept_code_id');
  }

  public function purchaseRequestQuote(): BelongsTo
  {
    return $this->belongsTo(PurchaseRequestQuote::class, 'purchase_request_quote_id');
  }
}
