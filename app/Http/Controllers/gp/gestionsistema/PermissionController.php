<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexPermissionRequest;
use App\Http\Requests\gp\gestionsistema\StoreBulkPermissionRequest;
use App\Http\Requests\gp\gestionsistema\StoreMultiplePermissionRoleRequest;
use App\Http\Requests\gp\gestionsistema\StorePermissionRoleRequest;
use App\Http\Services\gp\gestionsistema\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
  protected PermissionService $service;

  public function __construct(PermissionService $service)
  {
    $this->service = $service;
  }

  /**
   * Listar permisos con filtros
   */
  public function index(IndexPermissionRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Sincronizar permisos de un rol (reemplaza todos)
   */
  public function syncToRole(StoreMultiplePermissionRoleRequest $request)
  {
    try {
      return $this->success($this->service->syncPermissionsToRole(
        $request->role_id,
        $request->permissions
      ));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remover permiso de un rol
   */
  public function removeFromRole(Request $request)
  {
    try {
      $request->validate([
        'role_id' => 'required|exists:config_roles,id',
        'permission_id' => 'required|exists:permission,id',
      ]);

      $this->service->removePermissionFromRole(
        $request->role_id,
        $request->permission_id
      );

      return $this->success(['message' => 'Permiso removido correctamente']);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener permisos de un rol
   */
  public function getByRole(int $id)
  {
    try {
      return $this->success($this->service->getPermissionsByRole($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Sincronizar permisos de un módulo (crea nuevos, elimina los que no vienen)
   */
  public function bulkSync(StoreBulkPermissionRequest $request)
  {
    try {
      $result = $this->service->bulkSync($request->validated());
      return $this->success($result);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener acciones disponibles desde la configuración
   */
  public function getAvailableActions()
  {
    try {
      $actions = $this->service->getAvailableActions();
      return $this->success([
        'actions' => $actions,
        'default_actions' => config('permissions.default_actions'),
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
