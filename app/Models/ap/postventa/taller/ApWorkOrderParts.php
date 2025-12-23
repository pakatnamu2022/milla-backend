<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApWorkOrderParts extends Model
{
  use softDeletes;

  protected $table = 'ap_work_order_parts';

  protected $fillable = [
    'work_order_id',
    'group_number',
    'product_id',
    'warehouse_id',
    'quantity_used',
    'unit_cost',
    'unit_price',
    'discount_percentage',
    'subtotal',
    'tax_amount',
    'total_amount',
    'registered_by'
  ];

  const filters = [
    'work_order_id' => '=',
    'product_id' => '=',
    'warehouse_id' => '=',
    'group_number' => '='
  ];

  const sorts = [
    'id',
    'group_number',
    'created_at',
  ];

  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function registeredBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'registered_by');
  }
}
