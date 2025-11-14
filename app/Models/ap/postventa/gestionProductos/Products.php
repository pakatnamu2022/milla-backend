<?php

namespace App\Models\ap\postventa\gestionProductos;

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
        'part_number',
        'alternative_part_numbers',
        'barcode',
        'sku',
        'product_type',
        'minimum_stock',
        'maximum_stock',
        'current_stock',
        'cost_price',
        'sale_price',
        'wholesale_price',
        'tax_rate',
        'is_taxable',
        'sunat_code',
        'warehouse_location',
        'warranty_months',
        'reorder_point',
        'reorder_quantity',
        'lead_time_days',
        'weight',
        'dimensions',
        'image',
        'images',
        'technical_specifications',
        'compatible_vehicle_models',
        'is_fragile',
        'is_perishable',
        'expiration_date',
        'manufacturer',
        'country_of_origin',
        'notes',
        'status',
        'is_featured',
        'is_best_seller',
    ];

    protected $casts = [
        'alternative_part_numbers' => 'array',
        'dimensions' => 'array',
        'images' => 'array',
        'technical_specifications' => 'array',
        'compatible_vehicle_models' => 'array',
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'weight' => 'decimal:3',
        'is_taxable' => 'boolean',
        'is_fragile' => 'boolean',
        'is_perishable' => 'boolean',
        'is_featured' => 'boolean',
        'is_best_seller' => 'boolean',
        'expiration_date' => 'date',
    ];

    const filters = [
        'search' => [
            'code',
            'dyn_code',
            'nubefac_code',
            'name',
            'description',
            'part_number',
            'barcode',
            'sku',
            'manufacturer',
            'category.name',
            'brand.name'
        ],
        'product_category_id' => '=',
        'brand_id' => '=',
        'unit_measurement_id' => '=',
        'warehouse_id' => '=',
        'product_type' => '=',
        'status' => '=',
        'is_taxable' => '=',
        'is_fragile' => '=',
        'is_perishable' => '=',
        'is_featured' => '=',
        'is_best_seller' => '=',
        'cost_price' => '>=',
        'sale_price' => '>=',
        'current_stock' => '>=',
    ];

    const sorts = [
        'code',
        'dyn_code',
        'name',
        'part_number',
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

    public function setPartNumberAttribute($value)
    {
        $this->attributes['part_number'] = $value ? Str::upper(Str::ascii($value)) : null;
    }

    public function setBarcodeAttribute($value)
    {
        $this->attributes['barcode'] = $value ? Str::upper(Str::ascii($value)) : null;
    }

    public function setSkuAttribute($value)
    {
        $this->attributes['sku'] = $value ? Str::upper(Str::ascii($value)) : null;
    }

    public function setManufacturerAttribute($value)
    {
        $this->attributes['manufacturer'] = $value ? Str::upper(Str::ascii($value)) : null;
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

    public function getWholesalePriceWithTaxAttribute()
    {
        if (!$this->wholesale_price || !$this->is_taxable) {
            return $this->wholesale_price;
        }
        return $this->wholesale_price * (1 + ($this->tax_rate / 100));
    }

    public function getIsLowStockAttribute()
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    public function getNeedsReorderAttribute()
    {
        if (!$this->reorder_point) {
            return false;
        }
        return $this->current_stock <= $this->reorder_point;
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

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestSeller($query)
    {
        return $query->where('is_best_seller', true);
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
