<?php

namespace App\Models\ap\comercial;

use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApReceivingAccessoryStatus extends Model
{
  use SoftDeletes;

  protected $table = 'ap_receiving_accessory_status';

  protected $fillable = [
    'shipping_guide_id',
    'purchase_order_item_id',
    'description',
    'quantity',
    'received',
    'work_order_id',
  ];

  protected $casts = [
    'received' => 'boolean',
    'quantity'  => 'decimal:2',
  ];

  public function shippingGuide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
  }

  public function purchaseOrderItem(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
  }

  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  /**
   * Un accesorio está instalado cuando no llegó (received=false) pero su OT ya fue cerrada.
   */
  public function getIsInstalledAttribute(): bool
  {
    if ($this->received) {
      return true;
    }

    return $this->workOrder?->status_id === \App\Models\ap\ApMasters::CLOSED_WORK_ORDER_ID;
  }
}
