<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Products extends Model
{
  use SoftDeletes;

  protected $table = 'products';

  protected $fillable = [
    'code',
    'dyn_code',
    'nubefac_code',
    'name',
    'description',
    'product_category_id',
    'brand_id',
    'unit_measurement_id',
    'warehouse_id',
    'ap_class_article_id',
    'product_type',
    'minimum_stock',
    'maximum_stock',
    'current_stock',
    'cost_price',
    'sale_price',
    'tax_rate',
    'is_taxable',
    'sunat_code',
    'warranty_months',
    'status',
    'ap_class_article',
  ];

  protected $casts = [
    'minimum_stock' => 'decimal:2',
    'maximum_stock' => 'decimal:2',
    'current_stock' => 'decimal:2',
    'cost_price' => 'decimal:2',
    'sale_price' => 'decimal:2',
    'tax_rate' => 'decimal:2',
    'is_taxable' => 'boolean',
  ];

  const filters = [
    'search' => [
      'code',
      'dyn_code',
      'nubefac_code',
      'name',
      'description',
      'category.name',
      'brand.name'
    ],
    'product_category_id' => '=',
    'brand_id' => '=',
    'unit_measurement_id' => '=',
    'warehouse_id' => '=',
    'ap_class_article' => '=',
    'product_type' => '=',
    'status' => '=',
    'is_taxable' => '=',
    'cost_price' => '>=',
    'sale_price' => '>=',
    'current_stock' => '>=',
  ];

  const sorts = [
    'code',
    'dyn_code',
    'name',
    'current_stock',
    'cost_price',
    'sale_price',
    'created_at',
  ];

  // Mutators
  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = $value ? Str::upper(Str::ascii($value)) : null;
  }

  public function setNubefacCodeAttribute($value)
  {
    $this->attributes['nubefac_code'] = $value ? Str::upper(Str::ascii($value)) : null;
  }

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = $value ? Str::upper(Str::ascii($value)) : null;
  }

  // Relationships
  public function category()
  {
    return $this->belongsTo(ProductCategory::class, 'product_category_id');
  }

  public function brand()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id');
  }

  public function unitMeasurement()
  {
    return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id');
  }

  public function warehouse()
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function articleClass()
  {
    return $this->belongsTo(ApClassArticle::class, 'ap_class_article_id');
  }

  // Accessors
  public function getPriceWithTaxAttribute()
  {
    if (!$this->is_taxable) {
      return $this->sale_price;
    }
    return $this->sale_price * (1 + ($this->tax_rate / 100));
  }

  public function getCostWithTaxAttribute()
  {
    if (!$this->is_taxable) {
      return $this->cost_price;
    }
    return $this->cost_price * (1 + ($this->tax_rate / 100));
  }

  public function getIsLowStockAttribute()
  {
    return $this->current_stock <= $this->minimum_stock;
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'ACTIVE');
  }

  public function scopeLowStock($query)
  {
    return $query->whereColumn('current_stock', '<=', 'minimum_stock');
  }

  public function scopeOutOfStock($query)
  {
    return $query->where('current_stock', 0);
  }

  public function scopeByCategory($query, $categoryId)
  {
    return $query->where('product_category_id', $categoryId);
  }

  public function scopeByBrand($query, $brandId)
  {
    return $query->where('brand_id', $brandId);
  }

  public function scopeByType($query, $type)
  {
    return $query->where('product_type', $type);
  }
}
