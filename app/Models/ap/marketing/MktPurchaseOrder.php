<?php

namespace App\Models\ap\marketing;

use App\Models\BaseModel;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MktPurchaseOrder extends BaseModel
{
  use SoftDeletes;

  protected $table = 'mkt_purchase_orders';

  const STATUS_DRAFT           = 'draft';
  const STATUS_SENT            = 'sent';
  const STATUS_IN_EXECUTION    = 'in_execution';
  const STATUS_PENDING_SUPPORT = 'pending_support';
  const STATUS_SUPPORTED       = 'supported';
  const STATUS_PENDING_BILLING = 'pending_billing';
  const STATUS_BILLED          = 'billed';
  const STATUS_CLOSED          = 'closed';
  const STATUS_CANCELLED       = 'cancelled';

  protected $fillable = [
    'activity_id',
    'proposal_id',
    'supplier_id',
    'currency_id',
    'number',
    'amount',
    'issue_date',
    'status',
    'sent_at',
    'billed_at',
    'notes',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'amount'     => 'decimal:2',
    'issue_date' => 'date',
    'sent_at'    => 'datetime',
    'billed_at'  => 'datetime',
  ];

  const filters = [
    'search'      => ['number', 'notes'],
    'activity_id' => '=',
    'proposal_id' => '=',
    'supplier_id' => '=',
    'status'      => '=',
    'currency_id' => '=',
  ];

  const sorts = [
    'number',
    'amount',
    'issue_date',
    'status',
    'sent_at',
    'billed_at',
    'created_at',
  ];

  public function activity(): BelongsTo
  {
    return $this->belongsTo(MktActivity::class, 'activity_id');
  }

  public function proposal(): BelongsTo
  {
    return $this->belongsTo(MktProposal::class, 'proposal_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(\App\Models\ap\comercial\BusinessPartners::class, 'supplier_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function supports(): HasMany
  {
    return $this->hasMany(MktSupport::class, 'purchase_order_id');
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
