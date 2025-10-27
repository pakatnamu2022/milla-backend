<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PermissionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\Role;
use App\Models\gp\gestionsistema\RolePermission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionService extends BaseService
{
  protected $model = Permission::class;

  /**
   * Remover un permiso de un rol
   */
  public function removePermissionFromRole(int $roleId, int $permissionId): bool
  {
    try {
      RolePermission::where('role_id', $roleId)
        ->where('permission_id', $permissionId)
        ->delete();

      return true;
    } catch (\Exception $e) {
      throw new \Exception("Error al remover permiso: " . $e->getMessage());
    }
  }

  /**
   * Sincronizar permisos de un rol (elimina los no enviados, agrega los nuevos)
   *
   * @param int $roleId
   * @param array $permissionIds Array de IDs de permisos
   */
  public function syncPermissionsToRole(int $roleId, array $permissionIds): array
  {
    DB::beginTransaction();
    try {
      $role = Role::findOrFail($roleId);

      // Laravel sync maneja automáticamente agregar, actualizar y eliminar
      $role->permissions()->sync($permissionIds);

      DB::commit();
      return ['message' => 'Permisos sincronizados correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception("Error al sincronizar permisos: " . $e->getMessage());
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
