<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApOrderPurchaseRequestDetails extends Model
{
  use softDeletes;

  protected $table = 'ap_order_purchase_request_details';

  protected $fillable = [
    'order_purchase_request_id',
    'product_id',
    'quantity',
    'notes',
    'requested_delivery_date',
    'status',
  ];

  const filters = [
    'search' => ['notes'],
    'order_purchase_request_id' => '=',
    'product_id' => '=',
    'requested_delivery_date' => 'between',
  ];

  const sorts = [
    'id',
    'quantity',
    'requested_delivery_date',
    'created_at',
    'updated_at',
  ];

  protected $casts = [
    'requested_delivery_date' => 'datetime',
  ];

  // Status constants
  const STATUS_PENDING = 'pending';
  const STATUS_ORDERED = 'ordered';
  const STATUS_RECEIVED = 'received';
  const STATUS_REJECTED = 'rejected';

  public function orderPurchaseRequest(): BelongsTo
  {
    return $this->belongsTo(ApOrderPurchaseRequests::class, 'order_purchase_request_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function purchaseOrders(): BelongsToMany
  {
    return $this->belongsToMany(
      PurchaseOrder::class,
      'ap_order_purchase_request_detail_purchase_order',
      'ap_order_purchase_request_detail_id',
      'purchase_order_id'
    )->withTimestamps();
  }

  public function supplierOrders(): BelongsToMany
  {
    return $this->belongsToMany(
      ApSupplierOrder::class,
      'ap_order_purchase_request_detail_supplier_order',
      'ap_order_purchase_request_detail_id',
      'ap_supplier_order_id'
    )->withTimestamps();
  }
}
