<?php

namespace App\Models\ap\postventa\gestionProductos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferReceptionDetail extends Model
{
  use SoftDeletes;

  protected $table = 'transfer_reception_details';

  protected $fillable = [
    'transfer_reception_id',
    'product_id',
    'quantity_sent',
    'quantity_received',
    'observed_quantity',
    'reason_observation',
    'observation_notes',
    'unit_cost',
    'total_cost',
    'batch_number',
    'expiration_date',
  ];

  protected $casts = [
    'quantity_sent' => 'decimal:2',
    'quantity_received' => 'decimal:2',
    'observed_quantity' => 'decimal:2',
    'unit_cost' => 'decimal:2',
    'total_cost' => 'decimal:2',
    'expiration_date' => 'date',
  ];

  // Observation reasons
  const REASON_DAMAGED = 'DAMAGED';
  const REASON_MISSING = 'MISSING';
  const REASON_EXPIRED = 'EXPIRED';
  const REASON_DEFECTIVE = 'DEFECTIVE';
  const REASON_OTHER = 'OTHER';

  // Relationships
  public function transferReception(): BelongsTo
  {
    return $this->belongsTo(TransferReception::class, 'transfer_reception_id');
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Products::class, 'product_id');
  }

  // Accessors
  public function getQuantityAcceptedAttribute(): float
  {
    return $this->quantity_received - $this->observed_quantity;
  }

  public function getHasObservationsAttribute(): bool
  {
    return $this->observed_quantity > 0;
  }

  public function getObservationPercentageAttribute(): float
  {
    if ($this->quantity_sent == 0) {
      return 0;
    }

    return round(($this->observed_quantity / $this->quantity_sent) * 100, 2);
  }

  // Methods
  public function calculateTotalCost(): void
  {
    $this->total_cost = $this->quantity_received * ($this->unit_cost ?? 0);
    $this->save();
  }
}
