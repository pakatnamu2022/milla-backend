<?php

namespace App\Models\ap\compras;

use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierCreditNoteDetail extends Model
{
  use SoftDeletes;

  protected $table = 'supplier_credit_note_details';

  protected $fillable = [
    'supplier_credit_note_id',
    'product_id',
    'quantity',
    'unit_price',
    'discount_percentage',
    'tax_rate',
    'subtotal',
    'notes',
  ];

  protected $casts = [
    'quantity' => 'decimal:2',
    'unit_price' => 'decimal:2',
    'discount_percentage' => 'decimal:2',
    'tax_rate' => 'decimal:2',
    'subtotal' => 'decimal:2',
  ];

  // Relationships
  public function creditNote(): BelongsTo
  {
    return $this->belongsTo(SupplierCreditNote::class, 'supplier_credit_note_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  // Accessors
  public function getTotalWithoutTaxAttribute(): float
  {
    return $this->subtotal;
  }

  public function getTaxAmountAttribute(): float
  {
    return ($this->subtotal * $this->tax_rate) / 100;
  }

  public function getTotalWithTaxAttribute(): float
  {
    return $this->subtotal + $this->tax_amount;
  }

  public function getDiscountAmountAttribute(): float
  {
    return ($this->quantity * $this->unit_price * $this->discount_percentage) / 100;
  }

  // Scopes
  public function scopeByProduct($query, $productId)
  {
    return $query->where('product_id', $productId);
  }

  public function scopeByCreditNote($query, $creditNoteId)
  {
    return $query->where('supplier_credit_note_id', $creditNoteId);
  }

  // Methods
  public function calculateSubtotal(): void
  {
    $baseAmount = $this->quantity * $this->unit_price;
    $discountAmount = ($baseAmount * $this->discount_percentage) / 100;
    $this->subtotal = $baseAmount - $discountAmount;
    $this->save();
  }
}
