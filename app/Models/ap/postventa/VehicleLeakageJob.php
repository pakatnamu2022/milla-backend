<?php

namespace App\Models\ap\postventa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class VehicleLeakageJob extends Model
{
  protected $table = 'vehicle_leakage_jobs';

  protected $fillable = [
    'user_id',
    'original_filename',
    'temp_file_path',
    'status',
    'processed_file_path',
    'results',
    'error_message',
    'started_at',
    'completed_at',
  ];

  protected $casts = [
    'results' => 'array',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
  ];

  /**
   * Relación con el usuario que subió el archivo
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Verifica si el job está completo
   */
  public function isCompleted(): bool
  {
    return $this->status === 'completed';
  }

  /**
   * Verifica si el job falló
   */
  public function isFailed(): bool
  {
    return $this->status === 'failed';
  }

  /**
   * Verifica si el job está procesando
   */
  public function isProcessing(): bool
  {
    return $this->status === 'processing';
  }

  /**
   * Verifica si el job está pendiente
   */
  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  /**
   * Marca el job como procesando
   */
  public function markAsProcessing(): void
  {
    $this->update([
      'status' => 'processing',
      'started_at' => now(),
    ]);
  }

  /**
   * Marca el job como completado
   */
  public function markAsCompleted(string $processedFilePath, array $results): void
  {
    $this->update([
      'status' => 'completed',
      'processed_file_path' => $processedFilePath,
      'results' => $results,
      'completed_at' => now(),
    ]);
  }

  /**
   * Marca el job como fallido
   */
  public function markAsFailed(string $errorMessage): void
  {
    $this->update([
      'status' => 'failed',
      'error_message' => $errorMessage,
      'completed_at' => now(),
    ]);
  }
}