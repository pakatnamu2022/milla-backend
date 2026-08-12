<?php

namespace App\Models\ap\marketing;

use App\Models\BaseModel;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MktProposal extends BaseModel
{
  use SoftDeletes;

  protected $table = 'mkt_proposals';

  const STATUS_PENDING  = 'pending';
  const STATUS_APPROVED = 'approved';
  const STATUS_REJECTED = 'rejected';

  protected $fillable = [
    'activity_id',
    'supplier_id',
    'currency_id',
    'amount',
    'description',
    'status',
    'notes',
    'reviewed_by',
    'reviewed_at',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'amount'      => 'decimal:2',
    'reviewed_at' => 'datetime',
  ];

  const filters = [
    'search'      => ['description', 'notes'],
    'activity_id' => '=',
    'supplier_id' => '=',
    'status'      => '=',
    'currency_id' => '=',
  ];

  const sorts = [
    'amount',
    'status',
    'reviewed_at',
    'created_at',
  ];

  public function activity(): BelongsTo
  {
    return $this->belongsTo(MktActivity::class, 'activity_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(\App\Models\ap\comercial\BusinessPartners::class, 'supplier_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function reviewedBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
  }

  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'created_by');
  }

  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
  }
}
