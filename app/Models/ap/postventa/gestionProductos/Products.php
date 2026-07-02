<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Vehicles;
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
    'name',
    'description',
    'product_category_id',
    'brand_id',
    'unit_measurement_id',
    'ap_class_article_id',
    'warranty_months',
    'status',
  ];

  const filters = [
    'search' => [
      'code',
      'dyn_code',
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
    'warehouse_id' => 'scope',
  ];

  const sorts = [
    'code',
    'dyn_code',
    'name',
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

  /**
   * Válida que la cantidad tenga el número correcto de decimales según la unidad de medida
   *
   * @param float|int $quantity
   * @return void
   * @throws \Exception
   */
  public function validateDecimals($quantity): void
  {
    // Cargar la unidad de medida si no está cargada
    if (!$this->relationLoaded('unitMeasurement')) {
      $this->load('unitMeasurement');
    }

    if (!$this->unitMeasurement) {
      throw new \Exception("El producto '{$this->name}' no tiene una unidad de medida asignada.");
    }

    $numberDecimals = $this->unitMeasurement->number_decimals;

    // Validar que el valor sea numérico
    if (!is_numeric($quantity)) {
      throw new \Exception("La cantidad para el producto '{$this->name}' debe ser un valor numérico.");
    }

    // Si no acepta decimales, validar que sea entero
    if ($numberDecimals == 0 && floor($quantity) != $quantity) {
      throw new \Exception(
        "El producto '{$this->name}' solo acepta números enteros sin decimales según su unidad de medida."
      );
    }

    // Si acepta decimales, validar que no exceda el límite
    if ($numberDecimals > 0) {
      $stringValue = (string)$quantity;

      // Si tiene punto decimal, validar
      if (strpos($stringValue, '.') !== false) {
        $parts = explode('.', $stringValue);
        $decimalPart = $parts[1] ?? '';
        $actualDecimals = strlen($decimalPart);

        if ($actualDecimals > $numberDecimals) {
          // Construir mensaje amigable según el número de decimales
          $decimalText = $numberDecimals == 1
            ? "1 decimal"
            : "{$numberDecimals} decimales";

          throw new \Exception(
            "El producto '{$this->name}' solo acepta números con {$decimalText} según su unidad de medida. Ejemplo: " . number_format(12.34, $numberDecimals, '.', '')
          );
        }
      }
    }
  }

  /**
   * Valida que el producto tenga la misma marca que el vehículo de la cotización
   * Cadena de validación: Vehicles -> model -> family -> brand_id === Products.brand_id
   *
   * @param int|null $vehicleId ID del vehículo (puede ser null para cotizaciones sin vehículo)
   * @param int $productId ID del producto
   * @param string $productDescription Descripción del producto para mensajes de error
   * @return void
   * @throws \Exception
   */
  public static function validateProductBrandMatchesVehicle($vehicleId, int $productId, string $productDescription): void
  {
    // Si no hay vehículo, no validar marca (caso de cotizaciones sin vehículo asociado)
    if ($vehicleId === null) {
      return;
    }

    // Obtener el producto con su marca
    $product = self::with('brand')->find($productId);

    if (!$product) {
      throw new \Exception("Producto ({$productDescription}): No se encontró el producto.");
    }

    if (!$product->brand_id) {
      throw new \Exception("Producto ({$productDescription}): El producto no tiene una marca asignada.");
    }

    // Obtener el vehículo con su cadena de relaciones hasta la marca
    $vehicle = Vehicles::with('model.family.brand')->find($vehicleId);

    if (!$vehicle) {
      throw new \Exception("No se encontró el vehículo asociado a la cotización.");
    }

    if (!$vehicle->model) {
      throw new \Exception("El vehículo no tiene un modelo asignado.");
    }

    if (!$vehicle->model->family) {
      throw new \Exception("El modelo del vehículo no tiene una familia asignada.");
    }

    if (!$vehicle->model->family->brand_id) {
      throw new \Exception("La familia del modelo del vehículo no tiene una marca asignada.");
    }

    // Validar que las marcas coincidan
    if ($product->brand_id !== $vehicle->model->family->brand_id) {
      $productBrandName = $product->brand ? $product->brand->name : 'Desconocida';
      $vehicleBrandName = $vehicle->model->family->brand ? $vehicle->model->family->brand->name : 'Desconocida';

      throw new \Exception(
        "Producto ({$productDescription}): La marca del producto ({$productBrandName}) no coincide con la marca del vehículo ({$vehicleBrandName}). " .
        "Solo se pueden agregar productos de la misma marca que el vehículo."
      );
    }
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

  // Export Methods
  public function getReportData($filters = [])
  {
    $query = self::with([
      'category',
      'brand',
      'unitMeasurement',
      'articleClass',
      'warehouseStocks.warehouse'
    ])->withTotalStock();

    // Apply filters
    foreach ($filters as $filter) {
      $column = $filter['column'];
      $operator = $filter['operator'];
      $value = $filter['value'];

      if ($column === 'category_id' && $operator === '=') {
        $query->where('product_category_id', $value);
      } elseif ($column === 'brand_id' && $operator === '=') {
        $query->where('brand_id', $value);
      } elseif ($column === 'status' && $operator === '=') {
        $query->where('status', $value);
      } elseif ($column === 'product_type' && $operator === '=') {
        $query->where('product_type', $value);
      } elseif ($column === 'low_stock' && $operator === '=' && $value) {
        $query->whereHas('warehouseStocks', function ($q) {
          $q->whereRaw('quantity <= minimum_stock');
        });
      } elseif ($column === 'out_of_stock' && $operator === '=' && $value) {
        $query->whereHas('warehouseStocks', function ($q) {
          $q->where('quantity', '<=', 0);
        });
      }
    }

    $products = $query->get();

    return $products->map(function ($product) {
      return [
        'codigo' => $product->code,
        'codigo_dynamics' => $product->dyn_code,
        'nombre' => $product->name,
        'descripcion' => $product->description,
        'categoria' => $product->category ? $product->category->description : '',
        'marca' => $product->brand ? $product->brand->name : '',
        'unidad_medida' => $product->unitMeasurement ? $product->unitMeasurement->dyn_code : '',
        'clase_articulo' => $product->articleClass ? $product->articleClass->dyn_code : '',
        'garantia_meses' => $product->warranty_months,
        'stock_total' => $product->total_stock ?? 0,
        'stock_disponible' => $product->total_available_stock ?? 0,
        'estado' => $product->status,
        'fecha_creacion' => $product->created_at ? $product->created_at->format('d/m/Y H:i') : '',
      ];
    });
  }

  public function getReportableColumns()
  {
    return [
      'codigo' => 'Código',
      'codigo_dynamics' => 'Código Dynamics',
      'nombre' => 'Nombre',
      'descripcion' => 'Descripción',
      'categoria' => 'Categoría',
      'marca' => 'Marca',
      'unidad_medida' => 'Unidad de Medida',
      'clase_articulo' => 'Clase de Artículo',
      'garantia_meses' => 'Garantía (Meses)',
      'stock_total' => 'Stock Total',
      'stock_disponible' => 'Stock Disponible',
      'estado' => 'Estado',
      'fecha_creacion' => 'Fecha de Creación',
    ];
  }

  public function getReportStyles()
  {
    return [
      'headerBackgroundColor' => '4472C4',
      'headerFontColor' => 'FFFFFF',
      'headerFontSize' => 11,
      'headerBold' => true,
      'bodyFontSize' => 10,
      'freezePane' => 'A2',
      'autoFilter' => true,
    ];
  }

  public function getReportColorRules()
  {
    return [
      'estado' => [
        'ACTIVE' => '90EE90',   // Verde claro
        'INACTIVE' => 'FFB6C1', // Rosa claro
      ],
    ];
  }
}
