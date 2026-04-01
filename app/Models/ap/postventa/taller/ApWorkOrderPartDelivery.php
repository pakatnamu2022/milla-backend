<?php

namespace App\Models\ap\postventa\taller;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApWorkOrderPartDelivery extends Model
{
  use SoftDeletes;

  protected $table = 'ap_work_order_part_deliveries';

  protected $fillable = [
    'work_order_part_id',
    'delivered_to',
    'delivered_quantity',
    'delivered_date',
    'delivered_by',
    'is_received',
    'received_date',
    'received_signature_url',
    'received_by',
  ];

  protected $casts = [
    'delivered_quantity' => 'decimal:2',
    'delivered_date' => 'datetime',
    'received_date' => 'datetime',
    'is_received' => 'boolean',
  ];

  const filters = [
    'work_order_part_id' => '=',
    'delivered_to' => '=',
    'delivered_by' => '=',
    'is_received' => '='
  ];

  const sorts = [
    'id',
    'delivered_date',
    'received_date',
    'created_at',
  ];

  public function workOrderPart(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrderParts::class, 'work_order_part_id');
  }

  public function deliveredToUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'delivered_to');
  }

  public function deliveredByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'delivered_by');
  }

  public function receivedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'received_by');
  }
}