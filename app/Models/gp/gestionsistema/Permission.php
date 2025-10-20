<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends BaseModel
{
  use SoftDeletes;

  protected $table = 'permission';
  protected $primaryKey = 'id';

  protected $fillable = [
    'code',
    'name',
    'description',
    'module',
    'policy_method',
    'type',
    'is_active',
  ];

  protected $casts = [
    'is_active' => 'boolean',
  ];

  const filters = [
    'search' => [
      'code',
      'name',
      'description',
      'module',
    ],
    'code' => 'like',
    'name' => 'like',
    'module' => '=',
    'type' => '=',
    'is_active' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'code' => 'asc',
    'name' => 'asc',
    'module' => 'asc',
    'type' => 'asc',
  ];

  /**
   * Relación con roles a través de role_permission (many-to-many)
   */
  public function roles(): BelongsToMany
  {
    return $this->belongsToMany(
      Role::class,
      'role_permission',
      'permission_id',
      'role_id'
    )
      ->withPivot('granted')
      ->withTimestamps();
  }

  /**
   * Scope para filtrar solo permisos activos
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Scope para filtrar por módulo
   */
  public function scopeByModule($query, string $module)
  {
    return $query->where('module', $module);
  }

  /**
   * Scope para filtrar por tipo
   */
  public function scopeByType($query, string $type)
  {
    return $query->where('type', $type);
  }

  /**
   * Verifica si el permiso está asignado a un rol específico
   */
  public function isGrantedToRole(int $roleId): bool
  {
    return $this->roles()
      ->where('role_id', $roleId)
      ->wherePivot('granted', true)
      ->exists();
  }
}
