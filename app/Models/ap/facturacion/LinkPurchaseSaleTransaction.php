<?php

namespace App\Models\ap\facturacion;

use App\Models\ap\compras\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkPurchaseSaleTransaction extends Model
{
  use SoftDeletes;

  protected $table = 'link_purchase_sale_transactions';

  protected $fillable = [
    'billing_electronic_document_item_id',
    'purchase_order_item_id',
    'cantidad',
    'status',
  ];

  protected $casts = [
    'cantidad' => 'decimal:4',
  ];

  /**
   * Relaciones
   */
  public function electronicDocumentItem(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocumentItem::class, 'billing_electronic_document_item_id');
  }

  public function purchaseOrderItem(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
  }

  /**
   * Scopes
   */
  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  public function scopeReverted($query)
  {
    return $query->where('status', 'reverted');
  }

  public function scopeByElectronicDocumentItem($query, int $itemId)
  {
    return $query->where('billing_electronic_document_item_id', $itemId);
  }

  public function scopeByPurchaseOrderItem($query, int $itemId)
  {
    return $query->where('purchase_order_item_id', $itemId);
  }
}