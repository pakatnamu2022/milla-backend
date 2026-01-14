<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla de jobs de Laravel Queue
 *
 * @property int $id
 * @property string $queue
 * @property string $payload
 * @property int $attempts
 * @property int|null $reserved_at
 * @property int $available_at
 * @property int $created_at
 */
class Job extends Model
{
  /**
   * La tabla asociada al modelo
   */
  protected $table = 'jobs';

  /**
   * Indica si el modelo debe usar timestamps automáticos
   */
  public $timestamps = false;

  /**
   * Los atributos que se pueden asignar masivamente
   */
  protected $fillable = [
    'queue',
    'payload',
    'attempts',
    'reserved_at',
    'available_at',
    'created_at',
  ];

  /**
   * Los atributos que deben ser convertidos a tipos nativos
   */
  protected $casts = [
    'attempts' => 'integer',
    'reserved_at' => 'integer',
    'available_at' => 'integer',
    'created_at' => 'integer',
  ];

  /**
   * Scope para filtrar jobs por cola específica
   */
  public function scopeInQueue($query, string $queueName)
  {
    return $query->where('queue', $queueName);
  }

  /**
   * Scope para filtrar jobs por clase de job (busca en payload)
   */
  public function scopeOfClass($query, string $jobClass)
  {
    return $query->where('payload', 'like', '%' . addslashes($jobClass) . '%');
  }

  /**
   * Scope para filtrar jobs pendientes (no reservados)
   */
  public function scopePending($query)
  {
    return $query->whereNull('reserved_at');
  }

  /**
   * Scope para filtrar jobs reservados (en proceso)
   */
  public function scopeReserved($query)
  {
    return $query->whereNotNull('reserved_at');
  }

  /**
   * Scope para obtener jobs disponibles para procesamiento
   */
  public function scopeAvailable($query)
  {
    return $query->where('available_at', '<=', now()->timestamp);
  }

  /**
   * Scope para ordenar por fecha de creación
   */
  public function scopeOldestFirst($query)
  {
    return $query->orderBy('created_at', 'asc');
  }

  /**
   * Scope para ordenar por fecha de creación descendente
   */
  public function scopeNewestFirst($query)
  {
    return $query->orderBy('created_at', 'desc');
  }

  /**
   * Obtiene la fecha de creación formateada
   */
  public function getCreatedAtFormattedAttribute(): string
  {
    return date('Y-m-d H:i:s', $this->created_at);
  }

  /**
   * Obtiene la fecha de disponibilidad formateada
   */
  public function getAvailableAtFormattedAttribute(): string
  {
    return date('Y-m-d H:i:s', $this->available_at);
  }

  /**
   * Obtiene la fecha de reserva formateada
   */
  public function getReservedAtFormattedAttribute(): ?string
  {
    return $this->reserved_at ? date('Y-m-d H:i:s', $this->reserved_at) : null;
  }

  /**
   * Decodifica el payload JSON
   */
  public function getDecodedPayload(): ?object
  {
    return json_decode($this->payload);
  }

  /**
   * Obtiene el nombre de la clase del job desde el payload
   */
  public function getJobClassName(): ?string
  {
    $payload = $this->getDecodedPayload();
    return $payload->displayName ?? null;
  }
}
