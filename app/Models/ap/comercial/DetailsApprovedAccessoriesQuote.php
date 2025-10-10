<?php

namespace App\Models\ap\comercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailsApprovedAccessoriesQuote extends Model
{
  use SoftDeletes;

  protected $table = 'details_approved_accessories_quote';

  protected $fillable = [
    'quantity',
    'price',
    'total',
    'exchange_rate',
    'type_currency_id',
    'purchase_request_quote_id',
    'approved_accessory_id',
  ];
}
