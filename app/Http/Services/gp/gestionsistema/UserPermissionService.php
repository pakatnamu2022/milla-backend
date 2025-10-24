<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\View;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserPermissionService
{
  /**
   * Obtener todos los permisos del usuario agrupados por módulo
   *
   * @param int|null $userId
   * @return array
   */
  public function getUserPermissions(?int $userId = null): array
  {
    $user = $userId ? User::findOrFail($userId) : auth()->user();

    if (!$user) {
      throw new \Exception('Usuario no autenticado');
    }

    // Intentar obtener desde caché (1 hora)
    $cacheKey = "user_permissions_{$user->id}";

    return Cache::remember($cacheKey, 3600, function () use ($user) {
      // Obtener todos los permisos activos del usuario (granted = true)
      $permissions = $user->getAllPermissions()
        ->where('is_active', true)
        ->load('vista'); // Eager load de la relación con config_vista

      // Obtener vistas únicas asociadas a los permisos
      $vistas = View::whereIn('id', $permissions->pluck('vista_id')->filter())
        ->where('status_deleted', 1) // Solo vistas activas
        ->orderBy('id')
        ->get();

      // Agrupar permisos por módulo
      $permissionsByModule = $permissions->groupBy('module')->map(function ($perms) {
        return $perms->pluck('code')->toArray();
      });

      // Crear mapa plano de permisos (para verificaciones rápidas)
      $permissionsMap = $permissions->pluck('code')->mapWithKeys(function ($code) {
        return [$code => true];
      })->toArray();

      // Estructurar las vistas con sus permisos
      $modulesData = $vistas->map(function ($vista) use ($permissions) {
        // Filtrar permisos de esta vista
        $vistaPermissions = $permissions->where('vista_id', $vista->id);

        // Extraer acciones del código de permiso (ej: "vehicle_purchase_order.create" -> "create")
        $actions = $vistaPermissions->map(function ($permission) {
          $parts = explode('.', $permission->code);
          $actionCode = end($parts);

          return [
            'id' => $permission->id,
            'action_code' => $actionCode,
            'permission_code' => $permission->code,
            'name' => $permission->name,
            'type' => $permission->type,
          ];
        })->values();

        return [
          'id' => $vista->id,
          'code' => $vista->slug ?: $vista->descripcion,
          'name' => $vista->descripcion,
          'description' => $vista->submodule,
          'icon' => $vista->icon ?: $vista->icono,
          'route' => $vista->route ?: $vista->ruta,
          'parent_id' => $vista->parent_id,
          'actions' => $actions,
        ];
      });

      return [
        'user' => [
          'id' => $user->id,
          'name' => $user->name,
          'email' => $user->email,
          'role' => $user->role ? [
            'id' => $user->role->id,
            'name' => $user->role->nombre,
            'description' => $user->role->descripcion,
          ] : null,
        ],
        'modules' => $modulesData->values(),
        'permissions_by_module' => $permissionsByModule,
        'permissions' => $permissionsMap,
      ];
    });
  }

  /**
   * Obtener permisos de un módulo específico para el usuario
   *
   * @param string $moduleCode
   * @param int|null $userId
   * @return array
   */
  public function getModulePermissions(string $moduleCode, ?int $userId = null): array
  {
    $user = $userId ? User::findOrFail($userId) : auth()->user();

    if (!$user) {
      throw new \Exception('Usuario no autenticado');
    }

    // Obtener permisos del módulo
    $permissions = $user->getAllPermissions()
      ->where('is_active', true)
      ->where('module', $moduleCode);

    // Obtener vista asociada
    $vista = null;
    if ($permissions->isNotEmpty() && $permissions->first()->vista_id) {
      $vista = View::find($permissions->first()->vista_id);
    }

    $actions = $permissions->map(function ($permission) {
      $parts = explode('.', $permission->code);
      $actionCode = end($parts);

      return [
        'id' => $permission->id,
        'action_code' => $actionCode,
        'permission_code' => $permission->code,
        'name' => $permission->name,
        'type' => $permission->type,
      ];
    })->values();

    return [
      'module' => [
        'id' => $vista?->id,
        'code' => $moduleCode,
        'name' => $vista?->descripcion,
        'icon' => $vista?->icon ?: $vista?->icono,
        'route' => $vista?->route ?: $vista?->ruta,
      ],
      'actions' => $actions,
      'permissions' => $permissions->pluck('code')->toArray(),
    ];
  }

  /**
   * Verificar si el usuario tiene un permiso específico
   *
   * @param string $permissionCode
   * @param int|null $userId
   * @return bool
   */
  public function hasPermission(string $permissionCode, ?int $userId = null): bool
  {
    $user = $userId ? User::findOrFail($userId) : auth()->user();

    if (!$user) {
      return false;
    }

    return $user->hasPermission($permissionCode);
  }

  /**
   * Invalidar caché de permisos del usuario
   *
   * @param int $userId
   * @return void
   */
  public function clearUserPermissionsCache(int $userId): void
  {
    Cache::forget("user_permissions_{$userId}");
  }

  /**
   * Invalidar caché de todos los usuarios de un rol
   *
   * @param int $roleId
   * @return void
   */
  public function clearRolePermissionsCache(int $roleId): void
  {
    $userIds = DB::table('config_asig_role_user')
      ->where('role_id', $roleId)
      ->where('status_deleted', 1)
      ->pluck('user_id');

    foreach ($userIds as $userId) {
      $this->clearUserPermissionsCache($userId);
    }
  }

  /**
   * Obtener todos los módulos (vistas) disponibles en el sistema
   *
   * @return Collection
   */
  public function getAllModules(): Collection
  {
    return View::where('status_deleted', 1)
      ->whereNotNull('slug')
      ->orderBy('id')
      ->get()
      ->map(function ($vista) {
        return [
          'id' => $vista->id,
          'code' => $vista->slug,
          'name' => $vista->descripcion,
          'description' => $vista->submodule,
          'icon' => $vista->icon ?: $vista->icono,
          'route' => $vista->route ?: $vista->ruta,
          'parent_id' => $vista->parent_id,
        ];
      });
  }
}