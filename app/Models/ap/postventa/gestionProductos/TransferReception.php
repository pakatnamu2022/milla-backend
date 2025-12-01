<?php

namespace App\Models\ap\postventa\gestionProductos;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\User;
use App\Traits\HasReceptionBehavior;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TransferReception extends Model
{
  use SoftDeletes, HasReceptionBehavior;

  protected $table = 'transfer_receptions';

  protected $fillable = [
    'reception_number',
    'transfer_movement_id',
    'shipping_guide_id',
    'warehouse_id',
    'reception_date',
    'status',
    'notes',
    'received_by',
    'reviewed_by',
    'reviewed_at',
    'total_items',
    'total_quantity',
  ];

  protected $casts = [
    'reception_date' => 'date',
    'reviewed_at' => 'datetime',
    'total_items' => 'integer',
    'total_quantity' => 'decimal:2',
  ];

  const filters = [
    'search' => ['reception_number', 'shippingGuide.document_number'],
    'transfer_movement_id' => '=',
    'shipping_guide_id' => '=',
    'warehouse_id' => '=',
    'status' => '=',
    'reception_date' => 'between',
    'received_by' => '=',
  ];

  const sorts = [
    'reception_number',
    'reception_date',
    'total_quantity',
    'created_at',
  ];

  // Status constants
  const STATUS_PENDING = 'PENDING';
  const STATUS_APPROVED = 'APPROVED';
  const STATUS_REJECTED = 'REJECTED';

  // Boot method
  protected static function boot()
  {
    parent::boot();

    // When deleting a reception, also delete its details
    static::deleting(function ($reception) {
      $reception->details()->delete();
    });
  }

  // Mutators
  public function setNotesAttribute($value)
  {
    $this->attributes['notes'] = $value ? Str::upper(Str::ascii($value)) : null;
  }

  public function setReceptionNumberAttribute($value)
  {
    $this->attributes['reception_number'] = Str::upper(Str::ascii($value));
  }

  // Relationships
  public function transferMovement(): BelongsTo
  {
    return $this->belongsTo(InventoryMovement::class, 'transfer_movement_id');
  }

  public function shippingGuide(): BelongsTo
  {
    return $this->belongsTo(ShippingGuides::class, 'shipping_guide_id');
  }

  public function warehouse(): BelongsTo
  {
    return $this->belongsTo(Warehouse::class, 'warehouse_id');
  }

  public function receivedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'received_by');
  }

  public function reviewedByUser(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }

  public function details(): HasMany
  {
    return $this->hasMany(TransferReceptionDetail::class, 'transfer_reception_id');
  }

  // Accessors
  public function getIsPendingAttribute(): bool
  {
    return $this->status === self::STATUS_PENDING;
  }

  public function getIsApprovedAttribute(): bool
  {
    return $this->status === self::STATUS_APPROVED;
  }

  public function getIsRejectedAttribute(): bool
  {
    return $this->status === self::STATUS_REJECTED;
  }

  // Scopes
  public function scopePending($query)
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  public function scopeApproved($query)
  {
    return $query->where('status', self::STATUS_APPROVED);
  }

  public function scopeRejected($query)
  {
    return $query->where('status', self::STATUS_REJECTED);
  }

  public function scopeByWarehouse($query, $warehouseId)
  {
    return $query->where('warehouse_id', $warehouseId);
  }

  public function scopeByShippingGuide($query, $shippingGuideId)
  {
    return $query->where('shipping_guide_id', $shippingGuideId);
  }

  // Methods
  public static function generateReceptionNumber(): string
  {
    $year = date('Y');
    $lastReception = self::withTrashed()
      ->where('reception_number', 'LIKE', "RECV-TRANS-{$year}-%")
      ->orderBy('reception_number', 'desc')
      ->first();

    if ($lastReception) {
      $lastNumber = (int)substr($lastReception->reception_number, -4);
      $newNumber = $lastNumber + 1;
    } else {
      $newNumber = 1;
    }

    return sprintf('RECV-TRANS-%s-%04d', $year, $newNumber);
  }
}
