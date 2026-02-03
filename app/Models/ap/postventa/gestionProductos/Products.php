<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApMasters;
use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use App\Models\ap\maestroGeneral\UnitMeasurement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    'ap_class_article_id',
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
    'ap_class_article' => '=',
    'status' => '=',
    'is_taxable' => '=',
    'cost_price' => '>=',
    'sale_price' => '>=',
    'warehouse_id' => 'scope',
  ];

  const sorts = [
    'code',
    'dyn_code',
    'name',
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
    $this->attributes['name'] = Str::upper($value);
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = $value ? Str::upper($value) : null;
  }

  // Relationships
  public function category()
  {
    return $this->belongsTo(ApMasters::class, 'product_category_id');
  }

  public function brand()
  {
    return $this->belongsTo(ApVehicleBrand::class, 'brand_id');
  }

  public function unitMeasurement()
  {
    return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id');
  }

  public function articleClass()
  {
    return $this->belongsTo(ApClassArticle::class, 'ap_class_article_id');
  }

  // NEW: Relationship with warehouse stock
  public function warehouseStocks(): HasMany
  {
    return $this->hasMany(ProductWarehouseStock::class, 'product_id');
  }

  // Relationship with purchase order items
  public function purchaseOrderItems(): HasMany
  {
    return $this->hasMany(PurchaseOrderItem::class, 'product_id');
  }

  // Check if product has any purchase order
  public function hasPurchaseOrder(): bool
  {
    return $this->purchaseOrderItems()->exists();
  }

  // Get stock for a specific warehouse
  public function getStockForWarehouse($warehouseId)
  {
    return $this->warehouseStocks()->where('warehouse_id', $warehouseId)->first();
  }

  // Get total stock across all warehouses
  public function getTotalStockAttribute(): float
  {
    return $this->warehouseStocks()->sum('quantity');
  }

  // Get total available stock across all warehouses
  public function getTotalAvailableStockAttribute(): float
  {
    return $this->warehouseStocks()->sum('available_quantity');
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

  // Static Methods

  /**
   * Generate next correlative for product code
   * Takes into account deleted products to avoid duplicates
   */
  public static function generateNextCorrelative(): int
  {
    $lastProduct = self::withTrashed()
      ->orderBy('id', 'desc')
      ->first();

    return $lastProduct ? ($lastProduct->id + 1) : 1;
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('status', 'ACTIVE');
  }

  // NEW: Scopes for warehouse stock
  public function scopeWithWarehouseStock($query, $warehouseId = null)
  {
    return $query->with(['warehouseStocks' => function ($q) use ($warehouseId) {
      if ($warehouseId) {
        $q->where('warehouse_id', $warehouseId);
      }
    }]);
  }

  public function scopeWithTotalStock($query)
  {
    return $query->addSelect([
      'total_stock' => ProductWarehouseStock::selectRaw('SUM(quantity)')
        ->whereColumn('product_id', 'products.id')
    ])->addSelect([
      'total_available_stock' => ProductWarehouseStock::selectRaw('SUM(available_quantity)')
        ->whereColumn('product_id', 'products.id')
    ]);
  }

  public function scopeByCategory($query, $categoryId)
  {
    return $query->where('product_category_id', $categoryId);
  }

  public function scopeByBrand($query, $brandId)
  {
    return $query->where('brand_id', $brandId);
  }

  public function scopeInWarehouse($query, $warehouseId)
  {
    return $query->whereHas('warehouseStocks', function ($q) use ($warehouseId) {
      $q->where('warehouse_id', $warehouseId);
    });
  }

  // Scope for filtering by warehouse_id (used by Filterable trait)
  public function scopeWarehouseId($query, $warehouseId)
  {
    return $query->whereHas('warehouseStocks', function ($q) use ($warehouseId) {
      $q->where('warehouse_id', $warehouseId);
    });
  }
}
