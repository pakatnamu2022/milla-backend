<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApMasters;
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
    'cost_price',
    'average_cost',
    'sale_price',
    'tax_rate',
    'is_taxable',
    'last_movement_date',
    'status'
  ];

  protected $casts = [
    'quantity' => 'decimal:2',
    'quantity_in_transit' => 'decimal:2',
    'quantity_pending_credit_note' => 'decimal:2',
    'reserved_quantity' => 'decimal:2',
    'available_quantity' => 'decimal:2',
    'minimum_stock' => 'decimal:2',
    'maximum_stock' => 'decimal:2',
    'cost_price' => 'decimal:2',
    'average_cost' => 'decimal:2',
    'sale_price' => 'decimal:2',
    'last_movement_date' => 'datetime',
  ];

  const filters = [
    'search' => ['product.name', 'product.code', 'product.dyn_code'],
    'product_id' => '=',
    'warehouse_id' => '=',
    'quantity' => '>',
    'available_quantity' => '>',
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

  public static function validatePublicSalePrice(int $productId, int $sedeId, float $unitPrice): array
  {
    // Obtener el warehouse físico de postventa para la sede
    $warehouse = Warehouse::where('sede_id', $sedeId)
      ->where('is_physical_warehouse', true)
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_POSTVENTA)
      ->where('status', true)
      ->first();

    // Buscar el producto en el almacén de la sede
    $stockInSedeWarehouse = $warehouse
      ? self::where('product_id', $productId)
        ->where('warehouse_id', $warehouse->id)
        ->first()
      : null;

    // Si existe en el almacén de la sede, validar con ese precio
    if ($stockInSedeWarehouse) {
      $salePrice = $stockInSedeWarehouse->sale_price;

      // Si sale_price es 0 o null, buscar en otros almacenes
      if (!$salePrice || $salePrice == 0) {
        // Buscar en otros almacenes con precio válido
        $stocksInOtherWarehouses = self::where('product_id', $productId)
          ->where('sale_price', '>', 0)
          ->get();

        // Si no existe en ningún otro almacén con precio, no validar
        if ($stocksInOtherWarehouses->isEmpty()) {
          return ['valid' => true, 'message' => null, 'sale_price' => null];
        }

        // Obtener el precio menor de todos los almacenes
        $minSalePrice = $stocksInOtherWarehouses->min('sale_price');

        // Validar que unit_price no sea menor al precio menor
        if ($unitPrice < $minSalePrice) {
          return [
            'valid' => false,
            'message' => "El precio unitario ({$unitPrice}) no puede ser menor al precio de venta mínimo registrado ({$minSalePrice})",
            'sale_price' => $minSalePrice
          ];
        }

        return ['valid' => true, 'message' => null, 'sale_price' => $minSalePrice];
      }

      // Validar que unit_price no sea menor a sale_price
      if ($unitPrice < $salePrice) {
        return [
          'valid' => false,
          'message' => "El precio unitario ({$unitPrice}) no puede ser menor al precio de venta registrado ({$salePrice})",
          'sale_price' => $salePrice
        ];
      }

      return ['valid' => true, 'message' => null, 'sale_price' => $salePrice];
    }

    // Si no existe en el almacén de la sede, buscar en otros almacenes
    $stocksInOtherWarehouses = self::where('product_id', $productId)
      ->where('sale_price', '>', 0) // Solo almacenes con precio registrado
      ->get();

    // Si no existe en ningún almacén, no validar (como si fuera 0)
    if ($stocksInOtherWarehouses->isEmpty()) {
      return ['valid' => true, 'message' => null, 'sale_price' => null];
    }

    // Obtener el precio menor de todos los almacenes
    $minSalePrice = $stocksInOtherWarehouses->min('sale_price');

    // Validar que unit_price no sea menor al precio menor
    if ($unitPrice < $minSalePrice) {
      return [
        'valid' => false,
        'message' => "El precio unitario ({$unitPrice}) no puede ser menor al precio de venta mínimo registrado ({$minSalePrice})",
        'sale_price' => $minSalePrice
      ];
    }

    return ['valid' => true, 'message' => null, 'sale_price' => $minSalePrice];
  }
}
