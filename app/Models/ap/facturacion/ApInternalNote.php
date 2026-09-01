<?php

namespace App\Models\ap\facturacion;

use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApInternalNote extends BaseModel
{
  use SoftDeletes;

  protected $table = 'ap_internal_notes';

  protected $fillable = [
    'number',
    'work_order_id',
    'created_date',
    'closed_date',
    'status',
    'dyn_series_in',
    'dyn_series_out',
    'is_accounted_in',
    'is_accounted_out',
    'migration_status',
  ];

  protected $casts = [
    'created_date' => 'date',
    'closed_date' => 'date',
    'is_accounted_in' => 'boolean',
    'is_accounted_out' => 'boolean',
  ];

  const array filters = [
    'search' => ['number', 'workOrder.correlative'],
    'number' => '=',
    'work_order_id' => '=',
    'status' => '=',
    'created_date' => 'date_between',
    'closed_date' => 'date_between',
    'workOrder.sede_id' => '='
  ];

  const array sorts = ['id', 'number', 'created_date', 'closed_date'];

  // Status constants
  const STATUS_PENDING = 'pending';
  const STATUS_INVOICED = 'invoiced';

  // Migration status constants
  const MIGRATION_STATUS_COMPLETED = 'completed';
  const MIGRATION_STATUS_FAILED = 'failed';
  const MIGRATION_STATUS_PENDING = 'pending';
  const MIGRATION_STATUS_IN_PROGRESS = 'in_progress';
  const MIGRATION_STATUS_SKIPPED = 'skipped';

  /**
   * Boot method to auto-generate sequential number
   */
  protected static function booted()
  {
    static::creating(function ($model) {
      if (empty($model->number)) {
        $model->number = self::generateNextNumber();
      }
      if (empty($model->created_date)) {
        $model->created_date = now();
      }
    });
  }

  /**
   * Generate next sequential number
   * Format: IN-ddmmyy-correlative (e.g., IN-020826-1)
   */
  public static function generateNextNumber(): string
  {
    $today = now();
    $datePrefix = $today->format('dmy'); // Format: ddmmyy (e.g., 020826 for August 2, 2026)
    $prefix = "IN-{$datePrefix}-";

    // Find the last note created today
    $lastNote = self::withTrashed()
      ->where('number', 'LIKE', $prefix . '%')
      ->orderBy('id', 'desc')
      ->first();

    if (!$lastNote) {
      return $prefix . '1';
    }

    // Extract correlative from format IN-ddmmyy-1
    $parts = explode('-', $lastNote->number);
    $lastCorrelative = (int)end($parts);
    $nextCorrelative = $lastCorrelative + 1;

    return $prefix . $nextCorrelative;
  }

  /**
   * Relationships
   */
  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
  }

  public function electronicDocuments(): BelongsToMany
  {
    return $this->belongsToMany(
      ElectronicDocument::class,
      'electronic_document_internal_notes',
      'internal_note_id',
      'electronic_document_id'
    )->withTimestamps();
  }

  public function inventoryMovements(): MorphMany
  {
    return $this->morphMany(InventoryMovement::class, 'reference');
  }

  public function migrationLogs(): HasMany
  {
    return $this->hasMany(VehiclePurchaseOrderMigrationLog::class, 'internal_note_id');
  }

  /**
   * Scopes
   */
  public function scopePending($query)
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  public function scopeInvoiced($query)
  {
    return $query->where('status', self::STATUS_INVOICED);
  }

  /**
   * Business methods
   */
  public function markAsInvoiced(?string $closedDate = null): void
  {
    $this->update([
      'status' => self::STATUS_INVOICED,
      'closed_date' => $closedDate ?? now()->toDateString(),
    ]);
  }

  public function isPending(): bool
  {
    return $this->status === self::STATUS_PENDING;
  }

  public function isInvoiced(): bool
  {
    return $this->status === self::STATUS_INVOICED;
  }
}
