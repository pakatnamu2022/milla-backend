<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WeightedAverageCostHistory
 *
 * Modelo para la tabla materializada del historial de costo promedio ponderado.
 * Cada registro representa un "snapshot" (fotografía) del estado del stock y costo
 * de un producto en un almacén después de aplicar un movimiento de inventario.
 *
 * PROPÓSITO:
 * - Evitar recálculos costosos en tiempo real
 * - Facilitar consultas históricas de costos
 * - Soportar auditoría y análisis de variaciones de costo
 * - Permitir recálculos retroactivos eficientes (ej: NC antigua)
 *
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $movement_id
 * @property string $movement_date
 * @property string|null $movement_type
 * @property string|null $movement_number
 * @property float $quantity_in
 * @property float $quantity_out
 * @property float $unit_cost_pen
 * @property float $stock_after_movement
 * @property float $average_cost_after_movement
 * @property \Carbon\Carbon|null $recalculated_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class WeightedAverageCostHistory extends Model
{
  protected $table = 'weighted_average_cost_history';

  protected $fillable = [
    'product_id',
    'warehouse_id',
    'movement_id',
    'movement_date',
    'movement_type',
    'movement_number',
    'quantity_in',
    'quantity_out',
    'unit_cost_pen',
    'stock_after_movement',
    'average_cost_after_movement',
    'recalculated_at',
  ];

  protected $casts = [
    'product_id' => 'integer',
    'warehouse_id' => 'integer',
    'movement_id' => 'integer',
    'movement_date' => 'date',
    'quantity_in' => 'decimal:4',
    'quantity_out' => 'decimal:4',
    'unit_cost_pen' => 'decimal:2',
    'stock_after_movement' => 'decimal:4',
    'average_cost_after_movement' => 'decimal:2',
    'recalculated_at' => 'datetime',
  ];

  // Filters for BaseService
  const filters = [
    'product_id' => '=',
    'warehouse_id' => '=',
    'movement_type' => 'in',
    'movement_date' => 'between',
  ];

  // Sorts for BaseService
  const sorts = [
    'movement_date',
    'created_at',
    'stock_after_movement',
    'average_cost_after_movement',
  ];

  // ==========================================
  // RELATIONSHIPS
  // ==========================================

  /**
   * Producto asociado al historial
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  /**
   * Almacén asociado al historial
   */
  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  /**
   * Movimiento de inventario que generó este snapshot
   * Puede ser NULL para snapshots de estado inicial
   */
  public function inventoryMovement(): BelongsTo
  {
    return $this->belongsTo(InventoryMovement::class, 'movement_id');
  }

  // ==========================================
  // ACCESSORS
  // ==========================================

  /**
   * Determina si este snapshot es de un movimiento INBOUND
   */
  public function getIsInboundAttribute(): bool
  {
    return $this->quantity_in > 0;
  }

  /**
   * Determina si este snapshot es de un movimiento OUTBOUND
   */
  public function getIsOutboundAttribute(): bool
  {
    return $this->quantity_out > 0;
  }

  /**
   * Obtiene la cantidad neta del movimiento (positiva para IN, negativa para OUT)
   */
  public function getNetQuantityAttribute(): float
  {
    return $this->quantity_in - $this->quantity_out;
  }

  /**
   * Determina si este snapshot fue recalculado retroactivamente
   */
  public function getWasRecalculatedAttribute(): bool
  {
    return $this->recalculated_at !== null;
  }

  // ==========================================
  // SCOPES
  // ==========================================

  /**
   * Scope: Filtrar por producto y almacén
   */
  public function scopeForProductWarehouse($query, int $productId, int $warehouseId)
  {
    return $query->where('product_id', $productId)
      ->where('warehouse_id', $warehouseId);
  }

  /**
   * Scope: Ordenar cronológicamente (más antiguo primero)
   */
  public function scopeChronological($query)
  {
    return $query->orderBy('movement_date', 'asc')
      ->orderBy('id', 'asc');
  }

  /**
   * Scope: Ordenar cronológicamente inverso (más reciente primero)
   */
  public function scopeReverseChronological($query)
  {
    return $query->orderBy('movement_date', 'desc')
      ->orderBy('id', 'desc');
  }

  /**
   * Scope: Solo movimientos INBOUND (entradas)
   */
  public function scopeInbound($query)
  {
    return $query->where('quantity_in', '>', 0);
  }

  /**
   * Scope: Solo movimientos OUTBOUND (salidas)
   */
  public function scopeOutbound($query)
  {
    return $query->where('quantity_out', '>', 0);
  }

  /**
   * Scope: Solo movimientos de un tipo específico
   */
  public function scopeByType($query, string $movementType)
  {
    return $query->where('movement_type', $movementType);
  }

  /**
   * Scope: Movimientos desde una fecha específica (inclusive)
   */
  public function scopeFromDate($query, $date)
  {
    return $query->where('movement_date', '>=', $date);
  }

  /**
   * Scope: Movimientos hasta una fecha específica (inclusive)
   */
  public function scopeToDate($query, $date)
  {
    return $query->where('movement_date', '<=', $date);
  }

  /**
   * Scope: Movimientos que fueron recalculados
   */
  public function scopeRecalculated($query)
  {
    return $query->whereNotNull('recalculated_at');
  }

  // ==========================================
  // MÉTODOS ESTÁTICOS DE UTILIDAD
  // ==========================================

  /**
   * Obtiene el último snapshot de un producto en un almacén
   *
   * @param int $productId
   * @param int $warehouseId
   * @return WeightedAverageCostHistory|null
   */
  public static function getLatestSnapshot(int $productId, int $warehouseId): ?WeightedAverageCostHistory
  {
    return static::forProductWarehouse($productId, $warehouseId)
      ->reverseChronological()
      ->first();
  }

  /**
   * Obtiene el snapshot anterior a una fecha específica
   * Útil para obtener el estado base antes de un recálculo
   *
   * @param int $productId
   * @param int $warehouseId
   * @param string $beforeDate
   * @return WeightedAverageCostHistory|null
   */
  public static function getSnapshotBeforeDate(int $productId, int $warehouseId, string $beforeDate): ?WeightedAverageCostHistory
  {
    return static::forProductWarehouse($productId, $warehouseId)
      ->where('movement_date', '<', $beforeDate)
      ->reverseChronological()
      ->first();
  }

  /**
   * Elimina todos los snapshots desde una fecha específica
   * Útil antes de un recálculo retroactivo
   *
   * @param int $productId
   * @param int $warehouseId
   * @param string $fromDate
   * @return int Número de registros eliminados
   */
  public static function deleteFromDate(int $productId, int $warehouseId, string $fromDate): int
  {
    return static::forProductWarehouse($productId, $warehouseId)
      ->fromDate($fromDate)
      ->delete();
  }

  /**
   * Obtiene el historial completo de un producto en un almacén
   *
   * @param int $productId
   * @param int $warehouseId
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function getFullHistory(int $productId, int $warehouseId)
  {
    return static::forProductWarehouse($productId, $warehouseId)
      ->chronological()
      ->get();
  }
}