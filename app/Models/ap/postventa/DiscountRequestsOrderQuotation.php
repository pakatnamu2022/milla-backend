<?php

namespace App\Models\ap\postventa;

use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\User;
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
    'boss_id',
    'advisor_id',
    'reviewed_by_id',
    'request_date',
    'requested_discount_percentage',
    'requested_discount_amount',
    'review_date',
    'type',
    'item_type',
    'status',
  ];

  protected $casts = [
    'request_date' => 'datetime',
    'review_date' => 'datetime',
    'requested_discount_percentage' => 'float',
    'requested_discount_amount' => 'float',
  ];

  const filters = [
    'ap_order_quotation_id' => '=',
    'ap_order_quotation_detail_id' => '=',
    'manager_id' => '=',
    'reviewed_by_id' => '=',
    'type' => 'in',
    'status' => 'in',
  ];

  const sorts = [
    'id',
    'request_date',
    'review_date',
    'created_at',
    'updated_at',
  ];

  const TYPE_GLOBAL = 'GLOBAL';
  const TYPE_PARTIAL = 'PARTIAL';

  const STATUS_PENDING = 'pending';
  const STATUS_APPROVED = 'approved';
  const STATUS_REJECTED = 'rejected';

  public function scopeNotApproved(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  public function scopePending(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  public function scopeApproved(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_APPROVED);
  }

  public function scopeRejected(Builder $query): Builder
  {
    return $query->where('status', self::STATUS_REJECTED);
  }

  public function apOrderQuotation()
  {
    return $this->belongsTo(ApOrderQuotations::class, 'ap_order_quotation_id');
  }

  public function apOrderQuotationDetail()
  {
    return $this->belongsTo(ApOrderQuotationDetails::class, 'ap_order_quotation_detail_id');
  }

  public function manager()
  {
    return $this->belongsTo(User::class, 'manager_id');
  }

  public function boss()
  {
    return $this->belongsTo(User::class, 'boss_id');
  }

  public function advisor()
  {
    return $this->belongsTo(User::class, 'advisor_id');
  }

  public function reviewer()
  {
    return $this->belongsTo(User::class, 'reviewed_by_id');
  }

  // Mantener compatibilidad temporal con código existente
  public function approver()
  {
    return $this->reviewer();
  }
}
