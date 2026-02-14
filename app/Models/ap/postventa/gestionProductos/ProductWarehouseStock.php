<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWarehouseStock extends Model
{
  protected $table = 'product_warehouse_stock';

  protected $fillable = [
    'product_id',
    'warehouse_id',
    'quantity',
    'quantity_in_transit',
    'quantity_pending_credit_note',
    'reserved_quantity',
    'available_quantity',
    'minimum_stock',
    'maximum_stock',
    'last_movement_date',
  ];

  protected $casts = [
    'quantity' => 'decimal:2',
    'quantity_in_transit' => 'decimal:2',
    'quantity_pending_credit_note' => 'decimal:2',
    'reserved_quantity' => 'decimal:2',
    'available_quantity' => 'decimal:2',
    'minimum_stock' => 'decimal:2',
    'maximum_stock' => 'decimal:2',
    'last_movement_date' => 'datetime',
  ];

  const filters = [
    'search' => ['product.name', 'product.code', 'product.dyn_code'],
    'product_id' => '=',
    'warehouse_id' => '=',
  ];

  const sorts = [
    'quantity',
    'available_quantity',
    'last_movement_date',
    'created_at',
  ];

  // Relationships
  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  // Accessors
  public function getIsLowStockAttribute(): bool
  {
    return $this->quantity <= $this->minimum_stock;
  }

  public function getIsOutOfStockAttribute(): bool
  {
    return $this->quantity <= 0;
  }

  public function getStockStatusAttribute(): string
  {
    if ($this->quantity <= 0) {
      return 'OUT_OF_STOCK';
    }
    if ($this->quantity <= $this->minimum_stock) {
      return 'LOW_STOCK';
    }
    if ($this->maximum_stock && $this->quantity >= $this->maximum_stock) {
      return 'OVER_STOCK';
    }
    return 'NORMAL';
  }

  public function getTotalExpectedStockAttribute(): float
  {
    return $this->quantity + $this->quantity_in_transit;
  }

  // Scopes
  public function scopeLowStock($query)
  {
    return $query->whereColumn('quantity', '<=', 'minimum_stock');
  }

  public function scopeOutOfStock($query)
  {
    return $query->where('quantity', '<=', 0);
  }

  public function scopeByProduct($query, $productId)
  {
    return $query->where('product_id', $productId);
  }

  public function scopeByWarehouse($query, $warehouseId)
  {
    return $query->where('warehouse_id', $warehouseId);
  }

  public function scopeWithAvailableStock($query)
  {
    return $query->where('available_quantity', '>', 0);
  }

  // Methods
  public function updateAvailableQuantity(): void
  {
    $this->available_quantity = $this->quantity - $this->reserved_quantity;
    $this->save();
  }

  public function removeStock(float $quantity): bool
  {
    if ($this->available_quantity < $quantity) {
      return false;
    }

    $this->quantity -= $quantity;
    $this->updateAvailableQuantity();
    $this->last_movement_date = now();
    $this->save();

    return true;
  }

  public function reserveStock(float $quantity): bool
  {
    if ($this->available_quantity < $quantity) {
      return false;
    }

    $this->reserved_quantity += $quantity;
    $this->updateAvailableQuantity();
    $this->save();

    return true;
  }

  public function releaseReservedStock(float $quantity): void
  {
    $this->reserved_quantity -= $quantity;
    if ($this->reserved_quantity < 0) {
      $this->reserved_quantity = 0;
    }
    $this->updateAvailableQuantity();
    $this->save();
  }
}
