<?php

namespace App\Models\ap\postventa\gestionProductos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWarehouseShelf extends Model
{
  protected $table = 'product_warehouse_shelf';

  protected $fillable = [
    'product_warehouse_stock_id',
    'product_shelf_id',
    'position',
  ];

  // Relationships
  public function productWarehouseStock(): BelongsTo
  {
    return $this->belongsTo(ProductWarehouseStock::class, 'product_warehouse_stock_id');
  }

  public function productShelf(): BelongsTo
  {
    return $this->belongsTo(ProductShelf::class, 'product_shelf_id');
  }
}