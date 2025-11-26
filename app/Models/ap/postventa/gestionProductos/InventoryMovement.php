<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\ApPostVentaMasters;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryMovement extends Model
{
  use SoftDeletes;

  protected $table = 'inventory_movements';

  protected $fillable = [
    'movement_number',
    'movement_type',
    'movement_date',
    'warehouse_id',
    'warehouse_destination_id',
    'reference_type',
    'reference_id',
    'user_id',
    'status',
    'notes',
    'total_items',
    'total_quantity',
    'reason_in_out_id',
    'created_at',
    'updated_at',
  ];

  protected $casts = [
    'movement_date' => 'date',
    'total_items' => 'integer',
    'total_quantity' => 'decimal:2',
  ];

  const filters = [
    'search' => ['movement_number', 'user.name', 'warehouse.dyn_code', 'warehouseDestination.dyn_code'],
    'movement_type' => 'in',
    'movement_date' => 'between',
    'warehouse_id' => '=',
    'warehouse_destination_id' => '=',
    'status' => '=',
    'user_id' => '=',
    'reason_in_out_id' => '=',
  ];

  const sorts = [
    'movement_number',
    'movement_date',
    'total_quantity',
    'created_at',
  ];

  // Movement types
  const TYPE_PURCHASE_RECEPTION = 'PURCHASE_RECEPTION';
  const TYPE_SALE = 'SALE';
  const TYPE_ADJUSTMENT_IN = 'ADJUSTMENT_IN';
  const TYPE_ADJUSTMENT_OUT = 'ADJUSTMENT_OUT';
  const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
  const TYPE_TRANSFER_IN = 'TRANSFER_IN';
  const TYPE_RETURN_IN = 'RETURN_IN';
  const TYPE_RETURN_OUT = 'RETURN_OUT';

  // Status
  const STATUS_DRAFT = 'DRAFT';
  const STATUS_APPROVED = 'APPROVED';
  const STATUS_IN_TRANSIT = 'IN_TRANSIT';
  const STATUS_CANCELLED = 'CANCELLED';

  // Boot method
  protected static function boot()
  {
    parent::boot();

    // When deleting a movement, also delete its details
    static::deleting(function ($movement) {
      // Delete all details associated with this movement
      $movement->details()->delete();
    });
  }

  // Mutators
  public function setNotesAttribute($value)
  {
    $this->attributes['notes'] = Str::upper(Str::ascii($value));
  }

  public function setMovementNumberAttribute($value)
  {
    $this->attributes['movement_number'] = Str::upper(Str::ascii($value));
  }

  // Relationships
  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function warehouseDestination(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_destination_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function reasonInOut(): BelongsTo
  {
    return $this->belongsTo(ApPostVentaMasters::class, 'reason_in_out_id');
  }

  public function details(): HasMany
  {
    return $this->hasMany(InventoryMovementDetail::class, 'inventory_movement_id');
  }

  public function reference(): MorphTo
  {
    return $this->morphTo();
  }

  // Accessors
  public function getIsDraftAttribute(): bool
  {
    return $this->status === self::STATUS_DRAFT;
  }

  public function getIsApprovedAttribute(): bool
  {
    return $this->status === self::STATUS_APPROVED;
  }

  public function getIsCancelledAttribute(): bool
  {
    return $this->status === self::STATUS_CANCELLED;
  }

  public function getIsInboundAttribute(): bool
  {
    return in_array($this->movement_type, [
      self::TYPE_PURCHASE_RECEPTION,
      self::TYPE_ADJUSTMENT_IN,
      self::TYPE_TRANSFER_IN,
      self::TYPE_RETURN_IN,
    ]);
  }

  public function getIsOutboundAttribute(): bool
  {
    return in_array($this->movement_type, [
      self::TYPE_SALE,
      self::TYPE_ADJUSTMENT_OUT,
      self::TYPE_TRANSFER_OUT,
      self::TYPE_RETURN_OUT,
      self::TYPE_LOSS,
      self::TYPE_DAMAGE,
    ]);
  }

  public function getIsTransferAttribute(): bool
  {
    return in_array($this->movement_type, [
      self::TYPE_TRANSFER_OUT,
      self::TYPE_TRANSFER_IN,
    ]);
  }

  public function getIsInTransitAttribute(): bool
  {
    return $this->status === self::STATUS_IN_TRANSIT;
  }

  // Scopes
  public function scopeDraft($query)
  {
    return $query->where('status', self::STATUS_DRAFT);
  }

  public function scopeApproved($query)
  {
    return $query->where('status', self::STATUS_APPROVED);
  }

  public function scopeCancelled($query)
  {
    return $query->where('status', self::STATUS_CANCELLED);
  }

  public function scopeInTransit($query)
  {
    return $query->where('status', self::STATUS_IN_TRANSIT);
  }

  public function scopeByType($query, $type)
  {
    return $query->where('movement_type', $type);
  }

  public function scopeByWarehouse($query, $warehouseId)
  {
    return $query->where('warehouse_id', $warehouseId);
  }

  public function scopeInbound($query)
  {
    return $query->whereIn('movement_type', [
      self::TYPE_PURCHASE_RECEPTION,
      self::TYPE_ADJUSTMENT_IN,
      self::TYPE_TRANSFER_IN,
      self::TYPE_RETURN_IN,
    ]);
  }

  public function scopeOutbound($query)
  {
    return $query->whereIn('movement_type', [
      self::TYPE_SALE,
      self::TYPE_ADJUSTMENT_OUT,
      self::TYPE_TRANSFER_OUT,
      self::TYPE_RETURN_OUT,
      self::TYPE_LOSS,
      self::TYPE_DAMAGE,
    ]);
  }

  // Methods
  public function calculateTotals(): void
  {
    $this->total_items = $this->details()->count();
    $this->total_quantity = $this->details()->sum('quantity');
    $this->save();
  }

  public static function generateMovementNumber(): string
  {
    $year = date('Y');
    $lastMovement = self::withTrashed()
      ->where('movement_number', 'LIKE', "MOV-{$year}-%")
      ->orderBy('movement_number', 'desc')
      ->first();

    if ($lastMovement) {
      $lastNumber = (int)substr($lastMovement->movement_number, -4);
      $newNumber = $lastNumber + 1;
    } else {
      $newNumber = 1;
    }

    return sprintf('MOV-%s-%04d', $year, $newNumber);
  }
}
