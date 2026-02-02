<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApOrderQuotationDetails extends Model
{
  use softDeletes;

  protected $table = 'ap_order_quotation_details';

  protected $fillable = [
    'order_quotation_id',
    'item_type',
    'product_id',
    'description',
    'purchase_price',
    'quantity',
    'unit_measure',
    'unit_price',
    'discount_percentage',
    'total_amount',
    'observations',
    'retail_price_external',
    'exchange_rate',
    'freight_commission',
    'created_by',
    'status',
  ];

  const filters = [
    'search' => ['description', 'observations'],
    'order_quotation_id' => '=',
    'item_type' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'unit_price',
    'quantity',
    'total_amount',
    'created_at',
  ];

  public function setDescriptionAttribute($value): void
  {
    if ($value) {
      $this->attributes['description'] = strtoupper($value);
    }
  }

  public function orderQuotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'order_quotation_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
