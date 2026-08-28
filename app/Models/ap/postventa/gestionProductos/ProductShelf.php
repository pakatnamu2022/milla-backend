<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductShelf extends Model
{
  use SoftDeletes;

  protected $table = 'product_shelves';

  protected $fillable = [
    'warehouse_id',
    'code',
    'label',
    'notes',
    'status',
    'created_by',
  ];

  protected $casts = [
    'status' => 'boolean',
  ];

  const filters = [
    'search' => ['code', 'label'],
    'warehouse_id' => '=',
    'status' => '=',
  ];

  const sorts = [
    'code',
    'label',
    'created_at',
  ];

  // Relationships
  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function productStocks(): BelongsToMany
  {
    return $this->belongsToMany(
      ProductWarehouseStock::class,
      'product_warehouse_shelf',
      'product_shelf_id',
      'product_warehouse_stock_id'
    )->withPivot('position')->withTimestamps();
  }
}