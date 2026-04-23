<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
  Modelo para las solicitudes de compra
*/

class ApOrderPurchaseRequests extends Model
{
  use softDeletes;

  protected $table = 'ap_order_purchase_requests';

  protected $fillable = [
    'request_number',
    'ap_order_quotation_id',
    'purchase_order_id',
    'warehouse_id',
    'currency_id',
    'exchange_rate',
    'reviewed_by',
    'reviewed_at',
    'approved',
    'requested_date',
    'ordered_date',
    'received_date',
    'advisor_notified',
    'notified_at',
    'observations',
    'status',
    'requested_by',
    'supply_type'
  ];

  const filters = [
    'search' => ['request_number', 'observations'],
    'ap_order_quotation_id' => '=',
    'purchase_order_id' => '=',
    'warehouse_id' => '=',
    'currency_id' => '=',
    'requested_date' => 'between',
    'supply_type' => 'in',
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
    'reviewed_at' => 'datetime',
    'approved' => 'boolean',
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

  // SUPPLY TYPE CONSTANTS
  const STOCK = 'STOCK';
  const CENTRAL = 'CENTRAL';
  const IMPORTACION = 'IMPORTACION';

  // STATUS CONSTANTS
  const PENDING = 'pending';
  const ORDERED = 'ordered';
  const RECEIVED = 'received';
  const CANCELLED = 'cancelled';

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

  public function typeCurrency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
  }

  public function reviewedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }

  public function requestedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'requested_by');
  }

  public function details()
  {
    return $this->hasMany(ApOrderPurchaseRequestDetails::class, 'order_purchase_request_id');
  }
}
