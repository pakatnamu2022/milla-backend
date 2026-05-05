<?php

namespace App\Models\ap\comercial;

use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\repuestos\ApprovedAccessories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailsApprovedAccessoriesQuote extends Model
{
  use SoftDeletes;

  protected $table = 'details_approved_accessories_quote';

  protected $fillable = [
    'type',
    'quantity',
    'price',
    'additional_price',
    'total',
    'type_currency_id',
    'purchase_request_quote_id',
    'approved_accessory_id',
  ];

  public function approvedAccessory()
  {
    return $this->belongsTo(ApprovedAccessories::class, 'approved_accessory_id');
  }

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'type_currency_id');
  }
}
