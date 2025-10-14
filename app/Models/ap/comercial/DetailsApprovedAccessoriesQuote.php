<?php

namespace App\Models\ap\comercial;

use App\Models\ap\postventa\ApprovedAccessories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailsApprovedAccessoriesQuote extends Model
{
  use SoftDeletes;

  protected $table = 'details_approved_accessories_quote';

  protected $fillable = [
    'type',
    'quantity',
    'price',
    'total',
    'purchase_request_quote_id',
    'approved_accessory_id',
  ];

  public function approvedAccessory()
  {
    return $this->belongsTo(ApprovedAccessories::class, 'approved_accessory_id');
  }
}
