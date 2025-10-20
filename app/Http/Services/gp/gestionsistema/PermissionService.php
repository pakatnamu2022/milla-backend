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
  public function assignPermissionToRole(int $roleId, int $permissionId, bool $granted = true): bool
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

      return true;
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
  public function assignMultiplePermissionsToRole(int $roleId, array $permissions): bool
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
      return true;
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
  public function syncPermissionsToRole(int $roleId, array $permissionIds): bool
  {
    DB::beginTransaction();
    try {
      $role = Role::findOrFail($roleId);

      // Laravel sync maneja automáticamente agregar, actualizar y eliminar
      $role->permissions()->sync($permissionIds);

      DB::commit();
      return true;
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
  public function getPermissionsGroupedByModule(): JsonResource
  {
    $permissionGrouped = Permission::active()
      ->orderBy('module')
      ->orderBy('name')
      ->get()
      ->groupBy('module');

    return PermissionResource::collection($permissionGrouped);
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
    return $this->getFilteredResults(Permission::class, $request, [], [], PermissionResource::class);
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
}
