<?php

namespace App\Models\ap\marketing;

use App\Models\BaseModel;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MktSupport extends BaseModel
{
  use SoftDeletes;

  protected $table = 'mkt_supports';

  const TYPE_RECEIPT = 'receipt';
  const TYPE_INVOICE = 'invoice';
  const TYPE_PHOTO   = 'photo';
  const TYPE_REPORT  = 'report';
  const TYPE_OTHER   = 'other';

  protected $fillable = [
    'activity_id',
    'purchase_order_id',
    'type',
    'document_series',
    'document_number',
    'issue_date',
    'supplier_id',
    'currency_id',
    'amount',
    'file_path',
    'notes',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'issue_date' => 'date',
    'amount'     => 'decimal:2',
  ];

  const filters = [
    'search'            => ['document_series', 'document_number', 'notes'],
    'activity_id'       => '=',
    'purchase_order_id' => '=',
    'type'              => '=',
    'supplier_id'       => '=',
    'currency_id'       => '=',
  ];

  const sorts = [
    'type',
    'document_series',
    'document_number',
    'issue_date',
    'amount',
    'created_at',
  ];

  public function activity(): BelongsTo
  {
    return $this->belongsTo(MktActivity::class, 'activity_id');
  }

  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(MktPurchaseOrder::class, 'purchase_order_id');
  }

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(\App\Models\ap\comercial\BusinessPartners::class, 'supplier_id');
  }

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
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
