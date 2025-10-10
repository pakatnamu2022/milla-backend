<?php

namespace App\Http\Traits;

use App\Models\AuditLogs;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
  /**
   * Boot the auditable trait
   */
  public static function bootAuditable()
  {
    // Cuando se crea un registro
    static::created(function ($model) {
      $model->auditAction('created', [], $model->getAuditableAttributes());
    });

    // Cuando se actualiza un registro
    static::updated(function ($model) {
      $oldValues = [];
      $newValues = [];
      $changedFields = [];

      foreach ($model->getDirty() as $key => $newValue) {
        if ($model->shouldAuditField($key)) {
          $oldValue = $model->getOriginal($key);

          $oldValues[$key] = $oldValue;
          $newValues[$key] = $newValue;
          $changedFields[] = $key;
        }
      }

      if (!empty($changedFields)) {
        $model->auditAction('updated', $oldValues, $newValues, $changedFields);
      }
    });

    // Cuando se elimina un registro (soft delete)
    static::deleted(function ($model) {
      $action = $model->isForceDeleting() ? 'deleted' : 'deleted';
      $model->auditAction($action, $model->getAuditableAttributes(), []);
    });

    // Cuando se restaura un registro
    if (method_exists(static::class, 'restored')) {
      static::restored(function ($model) {
        $model->auditAction('restored', [], $model->getAuditableAttributes());
      });
    }
  }

  /**
   * Relación con los logs de auditoría
   */
  public function auditLogs(): MorphMany
  {
    return $this->morphMany(AuditLogs::class, 'auditable')->latest();
  }

  /**
   * Crear entrada de auditoría manual
   */
  public function audit(string $action, string $description, array $metadata = []): AuditLogs
  {
    return $this->auditAction($action, [], [], [], $description, $metadata);
  }

  /**
   * Auditar acción de visualización
   */
  public function auditView(string $description): AuditLogs
  {
    return $this->auditAction('viewed', [], [], [], $description);
  }

  /**
   * Crear entrada de auditoría
   */
  protected function auditAction(
    string $action,
    array  $oldValues = [],
    array  $newValues = [],
    array  $changedFields = [],
    string $description = '',
    array  $metadata = []
  ): AuditLogs
  {
    $user = auth()->user();

    return AuditLogs::create([
      'user_id' => $user?->id,
      'user_name' => $user?->name,
      'user_email' => $user?->email,
      'auditable_type' => get_class($this),
      'auditable_id' => $this->getKey(),
      'action' => $action,
      'old_values' => !empty($oldValues) ? $oldValues : null,
      'new_values' => !empty($newValues) ? $newValues : null,
      'changed_fields' => !empty($changedFields) ? $changedFields : null,
      'ip_address' => request()?->ip(),
      'user_agent' => request()?->userAgent(),
      'url' => request()?->fullUrl(),
      'method' => request()?->method(),
      'description' => $description,
      'metadata' => !empty($metadata) ? $metadata : null,
    ]);
  }

  /**
   * Obtener atributos auditables (sobrescribir si es necesario)
   */
  protected function getAuditableAttributes(): array
  {
    $hidden = $this->getHidden();
    $attributes = $this->getAttributes();

    // Remover campos sensibles por defecto
    $sensitiveFields = ['password', 'remember_token', 'email_verified_at'];

    return collect($attributes)
      ->except(array_merge($hidden, $sensitiveFields))
      ->toArray();
  }

  /**
   * Verificar si un campo debe ser auditado
   */
  protected function shouldAuditField(string $field): bool
  {
    // Campos que nunca se auditan
    $excludedFields = [
      'password',
      'remember_token',
      'email_verified_at',
      'updated_at',
      'created_at'
    ];

    // Si el modelo define campos excluidos específicos
    if (property_exists($this, 'auditExclude')) {
      $excludedFields = array_merge($excludedFields, $this->auditExclude);
    }

    // Si el modelo define solo campos específicos para auditar
    if (property_exists($this, 'auditInclude')) {
      return in_array($field, $this->auditInclude);
    }

    return !in_array($field, $excludedFields);
  }
}
