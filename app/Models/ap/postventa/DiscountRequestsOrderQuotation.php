<?php

namespace App\Models\ap\postventa;

use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountRequestsOrderQuotation extends Model
{
  use SoftDeletes;

  protected $table = 'discount_requests_order_quotation';

  protected $fillable = [
    'ap_order_quotation_id',
    'ap_order_quotation_detail_id',
    'manager_id',
    'approved_id',
    'request_date',
    'requested_discount_percentage',
    'requested_discount_amount',
    'approval_date',
    'type',
    'item_type',
  ];

  protected $casts = [
    'request_date' => 'datetime',
    'approval_date' => 'datetime',
    'requested_discount_percentage' => 'float',
    'requested_discount_amount' => 'float',
  ];

  const filters = [
    'ap_order_quotation_id' => '=',
    'ap_order_quotation_detail_id' => '=',
    'manager_id' => '=',
    'approved_id' => '=',
    'request_date' => 'between',
    'approval_date' => 'between',
    'type' => 'in',
  ];

  const sorts = [
    'id',
    'request_date',
    'approval_date',
    'created_at',
    'updated_at',
  ];

  const TYPE_GLOBAL = 'GLOBAL';
  const TYPE_PARTIAL = 'PARTIAL';

  public function scopeNotApproved(Builder $query): Builder
  {
    return $query->whereNull('approved_id')->whereNull('approval_date');
  }

  public function apOrderQuotation()
  {
    return $this->belongsTo(ApOrderQuotations::class, 'ap_order_quotation_id');
  }

  public function apOrderQuotationDetail()
  {
    return $this->belongsTo(ApOrderQuotationDetails::class, 'ap_order_quotation_detail_id');
  }
}
