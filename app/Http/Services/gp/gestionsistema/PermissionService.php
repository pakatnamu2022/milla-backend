<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PermissionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Access;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\Role;
use App\Models\gp\gestionsistema\RolePermission;
use App\Models\gp\gestionsistema\View;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionService extends BaseService
{
  protected $model = Permission::class;

  /**
   * @param int $roleId
   * @param array $permissionIds IDs que deben quedar asignados
   * @param array $permissionsToRemove IDs que deben desasignarse
   * @return array
   */
  public function savePermissionsToRole(int $roleId, array $permissionIds, array $permissionsToRemove = []): array
  {
    DB::beginTransaction();
    try {
      Role::findOrFail($roleId);

      // 1. Desasignar permisos removidos
      if (!empty($permissionsToRemove)) {
        $viewVistasToRemove = Permission::whereIn('id', $permissionsToRemove)
          ->whereNotNull('vista_id')
          ->where('code', 'like', '%.view')
          ->pluck('vista_id');

        if ($viewVistasToRemove->isNotEmpty()) {
          Access::where('role_id', $roleId)
            ->whereIn('vista_id', $viewVistasToRemove)
            ->delete();
        }

        RolePermission::where('role_id', $roleId)
          ->whereIn('permission_id', $permissionsToRemove)
          ->delete();
      }

      // 2. Upsert de permisos asignados
      foreach ($permissionIds as $permissionId) {
        $exists = RolePermission::where('role_id', $roleId)
          ->where('permission_id', $permissionId)
          ->exists();

        if (!$exists) {
          RolePermission::create([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted' => true,
          ]);
        } else {
          RolePermission::where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->update(['granted' => true, 'updated_at' => now()]);
        }
      }

      // 3. Reflejar permisos .view en config_asigxvistaxrole
      $this->updateViewPermissionsToAccess($roleId, $permissionIds);

      DB::commit();

      return ['permissions' => $this->getPermissionsByRole($roleId)];
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception("Error al guardar permisos: " . $e->getMessage());
    }
  }

  /**
   * Actualizar permisos de tipo "view" en la tabla config_asigxvistaxrole
   * Similar a syncViewPermissionsToAccess pero solo actualiza/crea, no elimina
   *
   * @param int $roleId
   * @param array $permissionIds Array de IDs de permisos a guardar
   */
  protected function updateViewPermissionsToAccess(int $roleId, array $permissionIds): void
  {
    // Obtener todos los permisos guardados con vista_id
    $savedPermissions = Permission::whereIn('id', $permissionIds)
      ->whereNotNull('vista_id')
      ->get();

    // Obtener permisos "view" que están siendo guardados
    $viewPermissions = $savedPermissions->filter(function ($permission) {
      return str_ends_with($permission->code, '.view');
    });

    // Obtener vistas con permiso "view" activo
    $vistasWithView = $viewPermissions->pluck('vista_id')->unique();

    // Para cada vista_id con permiso view, actualizar o crear el registro
    foreach ($vistasWithView as $vistaId) {
      Access::updateOrCreate(
        [
          'vista_id' => $vistaId,
          'role_id' => $roleId,
        ],
        [
          'ver' => true,
          'status_deleted' => 1,
        ]
      );
    }
  }

  /**
   * Retorna el diff de permisos sin aplicar cambios.
   * El frontend usa esta respuesta para mostrar el prompt de confirmación.
   *
   * @return array{to_assign: Collection, to_remove: Collection, unchanged: Collection}
   */
  public function previewPermissionsSync(int $roleId, array $permissionIds, array $permissionsToRemove): array
  {
    $role = Role::findOrFail($roleId);

    $currentIds = RolePermission::where('role_id', $roleId)
      ->where('granted', true)
      ->pluck('permission_id')
      ->toArray();

    $toAssignIds = array_diff($permissionIds, $currentIds);
    $toRemoveIds = array_intersect($permissionsToRemove, $currentIds);
    $unchangedIds = array_intersect($permissionIds, $currentIds);

    $fields = ['id', 'code', 'name', 'module'];

    return [
      'role'      => ['id' => $role->id, 'name' => $role->name],
      'to_assign' => Permission::whereIn('id', $toAssignIds)->get($fields),
      'to_remove' => Permission::whereIn('id', $toRemoveIds)->get($fields),
      'unchanged' => Permission::whereIn('id', $unchangedIds)->get($fields),
    ];
  }

  /**
   * Remover un permiso de un rol
   */
  public function removePermissionFromRole(int $roleId, int $permissionId): bool
  {
    DB::beginTransaction();
    try {
      // Obtener el permiso para verificar si es de tipo "view"
      $permission = Permission::find($permissionId);

      // Eliminar la relación role-permission
      RolePermission::where('role_id', $roleId)
        ->where('permission_id', $permissionId)
        ->delete();

      // Si el permiso es de tipo "view" y tiene vista_id, eliminar de config_asigxvistaxrole
      if ($permission && $permission->vista_id && str_ends_with($permission->code, '.view')) {
        Access::where('vista_id', $permission->vista_id)
          ->where('role_id', $roleId)
          ->delete();
      }

      DB::commit();
      return true;
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception("Error al remover permiso: " . $e->getMessage());
    }
  }

  /**
   * Obtener todos los permisos de un rol
   */
  public function getPermissionsByRole(int $roleId): Collection
  {
    return Permission::whereHas('roles', function ($query) use ($roleId) {
      $query->where('role_id', $roleId);
    })->get();
  }

  /**
   * Verificar si un rol tiene un permiso específico
   */
  public function roleHasPermission(int $roleId, string $permissionCode): bool
  {
    return Permission::where('code', $permissionCode)
      ->whereHas('roles', function ($query) use ($roleId) {
        $query->where('role_id', $roleId)
          ->wherePivot('granted', true);
      })
      ->where('is_active', true)
      ->exists();
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Permission::class,
      $request,
      Permission::filters,
      Permission::sorts,
      PermissionResource::class,
    );
  }

  public function find(int $id)
  {
    $view = Permission::where('id', $id)->first();
    if (!$view) {
      throw new Exception('Permiso no encontrado');
    }
    return $view;
  }

  /**
   * Crear un permiso individual
   *
   * @param array $data ['code', 'name', 'description', 'module', 'vista_id', 'policy_method', 'is_active']
   * @return PermissionResource
   */
  public function store(array $data): PermissionResource
  {
    DB::beginTransaction();
    try {
      // Validar que no exista un permiso con el mismo código (incluyendo soft-deleted)
      $existingPermission = Permission::withTrashed()->where('code', $data['code'])->first();

      if ($existingPermission) {
        // Si existe y está eliminado, restaurarlo y actualizarlo
        if ($existingPermission->trashed()) {
          $existingPermission->restore();
          $existingPermission->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'module' => $data['module'],
            'vista_id' => $data['vista_id'] ?? null,
            'policy_method' => $data['policy_method'],
            'is_active' => $data['is_active'] ?? true,
          ]);

          DB::commit();
          return new PermissionResource($existingPermission->fresh());
        }

        // Si existe y está activo, lanzar error
        throw new Exception("Ya existe un permiso con el código '{$data['code']}'");
      }

      // Crear el nuevo permiso
      $permission = Permission::create([
        'code' => $data['code'],
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'module' => $data['module'],
        'vista_id' => $data['vista_id'] ?? null,
        'policy_method' => $data['policy_method'],
        'is_active' => $data['is_active'] ?? true,
      ]);

      DB::commit();
      return new PermissionResource($permission);
    } catch (\Exception $e) {
      DB::rollBack();
      throw new Exception("Error al crear permiso: " . $e->getMessage());
    }
  }

  /**
   * Sincronizar permisos de un módulo (crea nuevos, mantiene existentes, elimina los que no vienen)
   *
   * @param array $data ['module', 'module_name', 'actions', 'vista_id', 'is_active']
   * @return array ['synced' => [...permissions], 'ids' => [...ids], 'created' => int, 'deleted' => int]
   */
  public function bulkSync(array $data): array
  {
    DB::beginTransaction();
    try {
      $module = $data['module'];
      $moduleName = $data['module_name'];
      $actions = $data['actions'];
      $vistaId = $data['vista_id'] ?? null;
      $isActive = $data['is_active'] ?? true;

      if ($vistaId) {
        $view = View::find($vistaId);
        if ($view->submodule || $view->route === null) {
          throw new \Exception("No se puede asignar la acción 'view' a una vista que es submódulo");
        }
      }

      $permissionsConfig = config('permissions.actions');
      $syncedPermissions = [];
      $createdCount = 0;
      $deletedCount = 0;

      // 1. Obtener todos los permisos existentes para este módulo (y vista_id si existe)
      $existingPermissionsQuery = Permission::where('module', $module);

      if ($vistaId !== null) {
        $existingPermissionsQuery->where('vista_id', $vistaId);
      } else {
        $existingPermissionsQuery->whereNull('vista_id');
      }

      $existingPermissions = $existingPermissionsQuery->get();
      $existingCodes = $existingPermissions->pluck('code')->toArray();

      // 2. Crear/obtener/restaurar los permisos según las acciones enviadas
      $expectedCodes = [];
      foreach ($actions as $action) {
        // Validar que la acción existe en el config
        if (!isset($permissionsConfig[$action])) {
          throw new \Exception("Acción '{$action}' no es válida");
        }

        $actionConfig = $permissionsConfig[$action];
        $code = "{$module}.{$action}";
        $expectedCodes[] = $code;

        // Verificar si ya existe el permiso (incluyendo soft-deleted)
        $existingPermission = Permission::withTrashed()->where('code', $code)->first();

        if ($existingPermission) {
          // Si existe pero está eliminado (soft delete), restaurarlo
          if ($existingPermission->trashed()) {
            $existingPermission->restore();
            $createdCount++; // Contar como "creado" porque fue restaurado
          }

          // SIEMPRE actualizar los datos (esté eliminado o activo)
          $existingPermission->update([
            'name' => "{$actionConfig['label']} {$moduleName}",
            'description' => $actionConfig['description'],
            'module' => $module,
            'vista_id' => $vistaId,
            'policy_method' => $actionConfig['policy_method'] ?? $action,
            'is_active' => $isActive,
          ]);

          // Agregar a la respuesta
          $syncedPermissions[] = new PermissionResource($existingPermission->fresh());
          continue;
        }

        // Crear el nuevo permiso (solo si no existe en absoluto)
        $permission = Permission::create([
          'code' => $code,
          'name' => "{$actionConfig['label']} {$moduleName}",
          'description' => $actionConfig['description'],
          'module' => $module,
          'vista_id' => $vistaId,
          'policy_method' => $actionConfig['policy_method'] ?? $action,
          'is_active' => $isActive,
        ]);

        $syncedPermissions[] = new PermissionResource($permission);
        $createdCount++;
      }

      // 3. Eliminar permisos que ya no están en la lista de acciones enviadas
      $codesToDelete = array_diff($existingCodes, $expectedCodes);

      if (!empty($codesToDelete)) {
        $deleted = Permission::whereIn('code', $codesToDelete)->delete();
        $deletedCount = $deleted;
      }

      DB::commit();

      return [
        'synced' => $syncedPermissions,
        'message' => "Sincronización completa: {$createdCount} creado(s), {$deletedCount} eliminado(s)",
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception("Error al sincronizar permisos: " . $e->getMessage());
    }
  }

  /**
   * Obtener las acciones disponibles desde la configuración
   */
  public function getAvailableActions(): array
  {
    $actions = config('permissions.actions');
    $formatted = [];

    foreach ($actions as $value => $config) {
      $formatted[] = [
        'value' => $value,
        'label' => $config['label'],
        'description' => $config['description'],
        'icon' => $config['icon'] ?? null,
      ];
    }

    return $formatted;
  }
}
