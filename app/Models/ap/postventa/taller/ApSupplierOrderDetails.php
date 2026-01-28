<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\postventa\gestionProductos\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApSupplierOrderDetails extends Model
{
  use softDeletes;

  protected $table = 'ap_supplier_order_details';

  protected $fillable = [
    'ap_supplier_order_id',
    'product_id',
    'unit_measurement_id',
    'note',
    'unit_price',
    'quantity',
    'total',
  ];

  const filters = [
    'ap_supplier_order_id' => '=',
    'product_id' => '=',
  ];

  const sorts = [
    'id',
    'unit_price',
    'quantity',
    'total',
    'created_at',
    'updated_at',
  ];

  public function apSupplierOrder(): BelongsTo
  {
    return $this->belongsTo(ApSupplierOrder::class, 'ap_supplier_order_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  public function unitMeasurement(): BelongsTo
  {
    return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id');
  }
}
