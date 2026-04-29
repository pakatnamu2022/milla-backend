<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
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
    'currency_id',
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

  public function currency(): BelongsTo
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_id');
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

  // Report methods for Excel Export
  public function getReportData($filters = [])
  {
    $query = self::with([
      'product',
      'warehouse',
      'currency'
    ]);

    // Apply filters
    foreach ($filters as $filter) {
      $column = $filter['column'];
      $operator = $filter['operator'];
      $value = $filter['value'];

      if ($column === 'warehouse_id' && $operator === '=') {
        $query->where('warehouse_id', $value);
      } elseif ($column === 'with_stock' && $operator === '=' && $value) {
        $query->where('quantity', '>', 0);
      } elseif ($column === 'without_stock' && $operator === '=' && $value) {
        $query->where('quantity', '<=', 0);
      }
    }

    $stocks = $query->get();

    // Get last movement for each stock
    return $stocks->map(function ($stock) {
      $lastMovement = InventoryMovement::whereHas('details', function ($q) use ($stock) {
        $q->where('product_id', $stock->product_id);
      })
        ->where(function ($q) use ($stock) {
          $q->where('warehouse_id', $stock->warehouse_id)
            ->orWhere('warehouse_destination_id', $stock->warehouse_id);
        })
        ->where('status', InventoryMovement::STATUS_APPROVED)
        ->orderBy('movement_date', 'desc')
        ->first();

      // Translate stock status to Spanish
      $statusTranslations = [
        'OUT_OF_STOCK' => 'Sin Stock',
        'LOW_STOCK' => 'Stock Bajo',
        'OVER_STOCK' => 'Sobre Stock',
        'NORMAL' => 'Normal',
      ];
      $translatedStatus = $statusTranslations[$stock->stock_status] ?? $stock->stock_status;

      return [
        'codigo_producto' => $stock->product?->code ?? 'N/A',
        'nombre_producto' => $stock->product?->name ?? 'N/A',
        'almacen' => $stock->warehouse?->description ?? 'N/A',
        'cantidad' => number_format($stock->quantity, 2),
        'cantidad_en_transito' => number_format($stock->quantity_in_transit, 2),
        'cantidad_reservada' => number_format($stock->reserved_quantity, 2),
        'cantidad_disponible' => number_format($stock->available_quantity, 2),
        'stock_minimo' => number_format($stock->minimum_stock, 2),
        'stock_maximo' => number_format($stock->maximum_stock, 2),
        'estado_stock' => $translatedStatus,
        'costo_promedio' => number_format($stock->average_cost, 2),
        'precio_venta' => number_format($stock->sale_price, 2),
        'moneda' => $stock->currency?->code ?? 'N/A',
        'ultimo_movimiento_fecha' => $lastMovement ? $lastMovement->movement_date->format('d/m/Y') : 'N/A',
        'ultimo_movimiento_tipo' => $lastMovement ? InventoryMovement::getMovementTypeLabel($lastMovement->movement_type) : 'N/A',
        'ultimo_movimiento_numero' => $lastMovement ? $lastMovement->movement_number : 'N/A',
        'ultimo_movimiento_usuario' => $lastMovement ? $lastMovement->user?->name : 'N/A',
        'fecha_ultimo_movimiento_stock' => $stock->last_movement_date ? $stock->last_movement_date->format('d/m/Y H:i:s') : 'N/A',
      ];
    });
  }

  public function getReportableColumns()
  {
    return [
      'codigo_producto' => 'Código Producto',
      'nombre_producto' => 'Nombre Producto',
      'almacen' => 'Almacén',
      'cantidad' => 'Cantidad',
      'cantidad_en_transito' => 'En Tránsito',
      'cantidad_reservada' => 'Reservada',
      'cantidad_disponible' => 'Disponible',
      'stock_minimo' => 'Stock Mínimo',
      'stock_maximo' => 'Stock Máximo',
      'estado_stock' => 'Estado Stock',
      'costo_promedio' => 'Costo Promedio',
      'precio_venta' => 'Precio Venta',
      'moneda' => 'Moneda',
      'ultimo_movimiento_fecha' => 'Fecha Últ. Movimiento',
      'ultimo_movimiento_tipo' => 'Tipo Últ. Movimiento',
      'ultimo_movimiento_numero' => 'Núm. Últ. Movimiento',
      'ultimo_movimiento_usuario' => 'Usuario Últ. Movimiento',
      'fecha_ultimo_movimiento_stock' => 'Fecha Últ. Actualización Stock',
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
      'estado_stock' => [
        'Sin Stock' => 'FF0000',      // Rojo
        'Stock Bajo' => 'FFA500',     // Naranja
        'Sobre Stock' => 'FFFF00',    // Amarillo
        'Normal' => '90EE90',          // Verde claro
      ],
    ];
  }
}
