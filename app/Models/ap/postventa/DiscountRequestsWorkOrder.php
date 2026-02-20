<?php

namespace App\Models\ap\postventa;

use App\Models\ap\postventa\taller\WorkOrderLabour;
use App\Models\ap\postventa\taller\ApWorkOrderParts;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountRequestsWorkOrder extends Model
{
  use SoftDeletes;

  protected $table = 'discount_requests_work_order';

  protected $fillable = [
    'ap_work_order_id',
    'manager_id',
    'reviewed_by_id',
    'part_labour_id',
    'part_labour_model',
    'request_date',
    'requested_discount_percentage',
    'requested_discount_amount',
    'review_date',
    'type',
    'status',
  ];

  protected $casts = [
    'request_date' => 'datetime',
    'review_date' => 'datetime',
    'requested_discount_percentage' => 'float',
    'requested_discount_amount' => 'float',
  ];

  const filters = [
    'ap_work_order_id' => '=',
    'manager_id' => '=',
    'reviewed_by_id' => '=',
    'request_date' => 'between',
    'review_date' => 'between',
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

  public function apWorkOrder()
  {
    return $this->belongsTo(ApWorkOrder::class, 'ap_work_order_id');
  }

  public function manager()
  {
    return $this->belongsTo(User::class, 'manager_id');
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

  /**
   * Relación polimórfica para el item (parte o labor)
   */
  public function partLabour()
  {
    return $this->morphTo('part_labour', 'part_labour_model', 'part_labour_id');
  }

  /**
   * Relación específica para WorkOrderParts
   */
  public function workOrderPart()
  {
    return $this->belongsTo(ApWorkOrderParts::class, 'part_labour_id')
      ->where('part_labour_model', ApWorkOrderParts::class);
  }

  /**
   * Relación específica para WorkOrderLabour
   */
  public function workOrderLabour()
  {
    return $this->belongsTo(WorkOrderLabour::class, 'part_labour_id')
      ->where('part_labour_model', WorkOrderLabour::class);
  }
}