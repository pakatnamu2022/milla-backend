<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApOrderPurchaseRequests extends Model
{
  use softDeletes;

  protected $table = 'ap_order_purchase_requests';

  protected $fillable = [
    'request_number',
    'ap_order_quotation_id',
    'purchase_order_id',
    'warehouse_id',
    'requested_date',
    'ordered_date',
    'received_date',
    'advisor_notified',
    'notified_at',
    'observations',
    'status',
  ];

  const filters = [
    'search' => ['request_number', 'observations'],
    'ap_order_quotation_id' => '=',
    'purchase_order_id' => '=',
    'warehouse_id' => '=',
    'requested_date' => 'between',
  ];

  const sorts = [
    'id',
    'request_number',
    'requested_date',
    'ordered_date',
    'received_date',
    'created_at',
    'updated_at',
  ];

  protected $casts = [
    'requested_date' => 'datetime',
    'ordered_date' => 'datetime',
    'received_date' => 'datetime',
    'notified_at' => 'datetime',
  ];

  // Boot method
  protected static function boot()
  {
    parent::boot();

    // When deleting a purchase request, also delete its details
    static::deleting(function ($purchaseRequest) {
      $purchaseRequest->details()->delete();
    });
  }

  public function apOrderQuotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'ap_order_quotation_id');
  }

  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function details()
  {
    return $this->hasMany(ApOrderPurchaseRequestDetails::class, 'order_purchase_request_id');
  }
}
