<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PermissionResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\Role;
use App\Models\gp\gestionsistema\RolePermission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionService extends BaseService implements BaseServiceInterface
{
  protected $model = Permission::class;

  /**
   * Asignar un permiso a un rol
   */
  public function assignPermissionToRole(int $roleId, int $permissionId, bool $granted = true): array
  {
    try {
      RolePermission::updateOrCreate(
        [
          'role_id' => $roleId,
          'permission_id' => $permissionId,
        ],
        [
          'granted' => $granted,
        ]
      );

      return ['message' => 'Permiso asignado correctamente'];
    } catch (\Exception $e) {
      throw new \Exception("Error al asignar permiso: " . $e->getMessage());
    }
  }

  /**
   * Asignar múltiples permisos a un rol
   *
   * @param int $roleId
   * @param array $permissions Array de IDs de permisos o array de ['permission_id' => int, 'granted' => bool]
   */
  public function assignMultiplePermissionsToRole(int $roleId, array $permissions): array
  {
    DB::beginTransaction();
    try {
      foreach ($permissions as $key => $value) {
        // Si es array asociativo con permission_id y granted
        if (is_array($value)) {
          $permissionId = $value['permission_id'];
          $granted = $value['granted'] ?? true;
        } else {
          // Si es array simple de IDs
          $permissionId = $value;
          $granted = true;
        }

        $this->assignPermissionToRole($roleId, $permissionId, $granted);
      }

      DB::commit();
      return ['message' => 'Permisos asignados correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception("Error al asignar múltiples permisos: " . $e->getMessage());
    }
  }

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
   * Obtener permisos agrupados por módulo
   */
  public function getPermissionsGroupedByModule()
  {
    return Permission::active()
      ->orderBy('module')
      ->orderBy('name')
      ->get()
      ->groupBy('module')
      ->map(function ($items) {
        // Convertir cada grupo a un array de recursos serializados
        return PermissionResource::collection($items)->resolve();
      });
  }

  /**
   * Obtener permisos de un módulo específico
   */
  public function getPermissionsByModule(string $module): Collection
  {
    return Permission::active()
      ->where('module', $module)
      ->orderBy('name')
      ->get();
  }

  /**
   * Obtener permisos por tipo
   */
  public function getPermissionsByType(string $type): Collection
  {
    return Permission::active()
      ->where('type', $type)
      ->orderBy('module')
      ->orderBy('name')
      ->get();
  }

  /**
   * Crear permiso con validación
   */
  public function store(mixed $data)
  {
    $permission = Permission::create($data);
    return new PermissionResource($permission);
  }

  /**
   * Actualizar permiso
   */
  public function update(mixed $data)
  {
    $permission = $this->find($data['id']);
    $permission->update($data);
    return new PermissionResource($permission);
  }

  /**
   * Activar/desactivar permiso
   */
  public function toggleActive(int $permissionId): bool
  {
    try {
      $permission = Permission::findOrFail($permissionId);
      $permission->is_active = !$permission->is_active;
      $permission->save();

      return true;
    } catch (\Exception $e) {
      throw new \Exception("Error al cambiar estado: " . $e->getMessage());
    }
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

  public function destroy(int $id)
  {
    $view = $this->find($id);
    $view->delete();
    return response()->json(['message' => 'Permiso eliminado correctamente']);
  }

  public function show(int $id)
  {
    return new PermissionResource($this->find($id));
  }

  /**
   * Crear múltiples permisos para un módulo/vista
   *
   * @param array $data ['module', 'module_name', 'actions', 'vista_id', 'type', 'is_active']
   * @return array ['created' => [...permissions], 'ids' => [...ids]]
   */
  public function bulkCreate(array $data): array
  {
    DB::beginTransaction();
    try {
      $module = $data['module'];
      $moduleName = $data['module_name'];
      $actions = $data['actions'];
      $vistaId = $data['vista_id'] ?? null;
      $type = $data['type'] ?? 'basic';
      $isActive = $data['is_active'] ?? true;

      $permissionsConfig = config('permissions.actions');
      $createdPermissions = [];
      $createdIds = [];

      foreach ($actions as $action) {
        // Validar que la acción existe en el config
        if (!isset($permissionsConfig[$action])) {
          throw new \Exception("Acción '{$action}' no es válida");
        }

        $actionConfig = $permissionsConfig[$action];
        $code = "{$module}.{$action}";

        // Verificar si ya existe el permiso
        $existingPermission = Permission::where('code', $code)->first();

        if ($existingPermission) {
          // Si ya existe, lo agregamos a la respuesta
          $createdPermissions[] = new PermissionResource($existingPermission);
          $createdIds[] = $existingPermission->id;
          continue;
        }

        // Crear el permiso
        $permission = Permission::create([
          'code' => $code,
          'name' => "{$actionConfig['label']} {$moduleName}",
          'description' => $actionConfig['description'],
          'module' => $module,
          'vista_id' => $vistaId,
          'policy_method' => $actionConfig['policy_method'] ?? $action,
          'type' => $type,
          'is_active' => $isActive,
        ]);

        $createdPermissions[] = new PermissionResource($permission);
        $createdIds[] = $permission->id;
      }

      DB::commit();

      return [
        'created' => $createdPermissions,
        'ids' => $createdIds,
        'count' => count($createdIds),
        'message' => count($createdIds) . ' permiso(s) creado(s) correctamente',
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      throw new \Exception("Error al crear permisos: " . $e->getMessage());
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
