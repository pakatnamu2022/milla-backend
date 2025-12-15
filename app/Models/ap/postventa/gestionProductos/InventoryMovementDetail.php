<?php

namespace App\Models\ap\postventa\gestionProductos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryMovementDetail extends Model
{
  use SoftDeletes;

  protected $table = 'inventory_movement_details';

  protected $fillable = [
    'inventory_movement_id',
    'product_id',
    'quantity',
    'unit_cost',
    'total_cost',
    'batch_number',
    'expiration_date',
    'notes',
  ];

  protected $casts = [
    'quantity' => 'decimal:2',
    'unit_cost' => 'decimal:2',
    'total_cost' => 'decimal:2',
    'expiration_date' => 'date',
  ];

  public function setNotesAttribute($value)
  {
    $this->attributes['notes'] = Str::upper(Str::ascii($value));
  }

  // Relationships
  public function movement(): BelongsTo
  {
    return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  // Accessors
  public function getIsInboundAttribute(): bool
  {
    return $this->quantity > 0;
  }

  public function getIsOutboundAttribute(): bool
  {
    return $this->quantity < 0;
  }

  public function getAbsoluteQuantityAttribute(): float
  {
    return abs($this->quantity);
  }

  // Scopes
  public function scopeByProduct($query, $productId)
  {
    return $query->where('product_id', $productId);
  }

  public function scopeByMovement($query, $movementId)
  {
    return $query->where('inventory_movement_id', $movementId);
  }

  public function scopeInbound($query)
  {
    return $query->where('quantity', '>', 0);
  }

  public function scopeOutbound($query)
  {
    return $query->where('quantity', '<', 0);
  }

  // Methods
  public function calculateTotalCost(): void
  {
    $this->total_cost = abs($this->quantity) * $this->unit_cost;
    $this->save();
  }
}
