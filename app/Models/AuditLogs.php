<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLogs extends Model
{
  /** @use HasFactory<\Database\Factories\AuditLogsFactory> */
  use HasFactory;

  protected $fillable = [
    'user_id',
    'user_name',
    'user_email',
    'auditable_type',
    'auditable_id',
    'action',
    'old_values',
    'new_values',
    'changed_fields',
    'ip_address',
    'user_agent',
    'url',
    'method',
    'request_data',
    'description',
    'metadata',
  ];

  protected $casts = [
    'old_values' => 'array',
    'new_values' => 'array',
    'changed_fields' => 'array',
    'request_data' => 'array',
    'metadata' => 'array',
    'created_at' => 'datetime',
  ];

  public $timestamps = false;

  /**
   * Relación polimórfica al modelo auditado
   */
  public function auditable(): MorphTo
  {
    return $this->morphTo();
  }

  /**
   * Relación con el usuario
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Scope para filtrar por acción
   */
  public function scopeAction($query, string $action)
  {
    return $query->where('action', $action);
  }

  /**
   * Scope para filtrar por modelo
   */
  public function scopeForModel($query, string $modelType)
  {
    return $query->where('auditable_type', $modelType);
  }

  /**
   * Scope para filtrar por usuario
   */
  public function scopeByUser($query, int $userId)
  {
    return $query->where('user_id', $userId);
  }

  /**
   * Scope para filtrar por rango de fechas
   */
  public function scopeDateRange($query, $startDate, $endDate)
  {
    return $query->whereBetween('created_at', [$startDate, $endDate]);
  }

  /**
   * Obtener descripción legible de la acción
   */
  public function getActionDescriptionAttribute(): string
  {
    $actions = [
      'created' => 'Creó',
      'updated' => 'Actualizó',
      'deleted' => 'Eliminó',
      'viewed' => 'Consultó',
      'restored' => 'Restauró',
    ];

    return $actions[$this->action] ?? 'Realizó acción en';
  }

  /**
   * Obtener resumen de cambios
   */
  public function getChangesSummaryAttribute(): array
  {
    if (!$this->changed_fields || empty($this->changed_fields)) {
      return [];
    }

    $summary = [];
    foreach ($this->changed_fields as $field) {
      $summary[] = [
        'field' => $field,
        'from' => $this->old_values[$field] ?? null,
        'to' => $this->new_values[$field] ?? null,
      ];
    }

    return $summary;
  }

  /**
   * Obtener nombre del modelo legible
   */
  public function getModelNameAttribute(): string
  {
    $parts = explode('\\', $this->auditable_type);
    return end($parts);
  }
}
