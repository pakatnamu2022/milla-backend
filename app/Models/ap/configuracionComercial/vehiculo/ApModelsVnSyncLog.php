<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApModelsVnSyncLog extends Model
{
  protected $table = 'ap_models_vn_sync_logs';

  protected $fillable = [
    'model_vn_id',
    'code',
    'status',
    'proceso_estado',
    'dynamics_payload',
    'error_message',
    'attempts',
    'last_attempt_at',
    'completed_at',
  ];

  protected $casts = [
    'dynamics_payload' => 'array',
    'last_attempt_at'  => 'datetime',
    'completed_at'     => 'datetime',
    'attempts'         => 'integer',
    'proceso_estado'   => 'integer',
  ];

  const STATUS_PENDING     = 'pending';
  const STATUS_IN_PROGRESS = 'in_progress';
  const STATUS_COMPLETED   = 'completed';
  const STATUS_FAILED      = 'failed';

  public function model(): BelongsTo
  {
    return $this->belongsTo(ApModelsVn::class, 'model_vn_id');
  }

  public function markAsInProgress(): void
  {
    $this->update([
      'status'          => self::STATUS_IN_PROGRESS,
      'proceso_estado'  => 0,
      'last_attempt_at' => now(),
      'attempts'        => $this->attempts + 1,
    ]);
  }

  public function markAsCompleted(array $payload): void
  {
    $this->update([
      'status'           => self::STATUS_COMPLETED,
      'proceso_estado'   => 1,
      'dynamics_payload' => $payload,
      'completed_at'     => now(),
      'error_message'    => null,
    ]);
  }

  public function markAsFailed(string $errorMessage): void
  {
    $this->update([
      'status'          => self::STATUS_FAILED,
      'error_message'   => $errorMessage,
      'last_attempt_at' => now(),
      'attempts'        => $this->attempts + 1,
    ]);
  }

  public function scopePending($query)
  {
    return $query->where('status', self::STATUS_PENDING);
  }

  public function scopeFailed($query)
  {
    return $query->where('status', self::STATUS_FAILED);
  }

  public function scopeCompleted($query)
  {
    return $query->where('status', self::STATUS_COMPLETED);
  }

  public function scopeInProgress($query)
  {
    return $query->where('status', self::STATUS_IN_PROGRESS);
  }
}
