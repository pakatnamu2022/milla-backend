<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    'vista_id',
    'policy_method',
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
    'is_active' => '=',
    'vista_id' => '=',
  ];

  const sorts = [
    'id' => 'asc',
    'code' => 'asc',
    'name' => 'asc',
    'module' => 'asc',
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
   * Relación con vista/módulo (config_vista)
   */
  public function vista(): BelongsTo
  {
    return $this->belongsTo(View::class, 'vista_id');
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
   * Verifica si el permiso está asignado a un rol específico
   */
  public function isGrantedToRole(int $roleId): bool
  {
    return $this->roles()
      ->where('role_id', $roleId)
      ->wherePivot('granted', true)
      ->exists();
  }

  /**
   * Obtener todos los usuarios que tienen este permiso (a través de roles)
   */
  public function users()
  {
    return User::whereHas('role', function ($query) {
      $query->whereHas('permissions', function ($subQuery) {
        $subQuery->where('permission_id', $this->id)
          ->where('granted', true);
      });
    });
  }

  /**
   * Clonar este permiso con un nuevo código
   */
  public function cloneWithCode(string $newCode, string $newName = null): self
  {
    return self::create([
      'code' => $newCode,
      'name' => $newName ?? $this->name,
      'description' => $this->description,
      'module' => $this->module,
      'vista_id' => $this->vista_id,
      'policy_method' => $this->policy_method,
      'is_active' => $this->is_active,
    ]);
  }

  /**
   * Generar permisos CRUD para un módulo
   */
  public static function generateCrudPermissions(string $module, ?int $vistaId = null, string $moduleName = null): array
  {
    $moduleName = $moduleName ?? ucwords(str_replace('_', ' ', $module));
    $permissions = [];

    $actions = [
      'view' => "Ver {$moduleName}",
      'create' => "Crear {$moduleName}",
      'edit' => "Editar {$moduleName}",
      'delete' => "Eliminar {$moduleName}",
    ];

    foreach ($actions as $action => $name) {
      $permissions[] = self::updateOrCreate(
        ['code' => "{$module}.{$action}"],
        [
          'name' => $name,
          'module' => $module,
          'vista_id' => $vistaId,
          'is_active' => true,
        ]
      );
    }

    return $permissions;
  }

  /**
   * Obtener definición exportable del permiso
   */
  public function toExportArray(): array
  {
    return [
      'code' => $this->code,
      'name' => $this->name,
      'description' => $this->description,
      'module' => $this->module,
      'is_active' => $this->is_active,
    ];
  }

  /**
   * Importar permisos desde un array
   */
  public static function importFromArray(array $permissions): int
  {
    $count = 0;
    foreach ($permissions as $permissionData) {
      self::updateOrCreate(
        ['code' => $permissionData['code']],
        $permissionData
      );
      $count++;
    }
    return $count;
  }

  /**
   * Obtener el nombre de la acción desde el código
   * Ejemplo: 'vehicle_purchase_order.create' -> 'create'
   */
  public function getActionAttribute(): string
  {
    $parts = explode('.', $this->code);
    return end($parts);
  }
}
