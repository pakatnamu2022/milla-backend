<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexPermissionRequest;
use App\Http\Requests\gp\gestionsistema\StoreMultiplePermissionRoleRequest;
use App\Http\Requests\gp\gestionsistema\StorePermissionRequest;
use App\Http\Requests\gp\gestionsistema\StorePermissionRoleRequest;
use App\Http\Requests\gp\gestionsistema\UpdatePermissionRequest;
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
   * Crear nuevo permiso
   */
  public function store(StorePermissionRequest $request)
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Mostrar un permiso específico
   */
  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Actualizar permiso
   */
  public function update(UpdatePermissionRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Eliminar permiso (soft delete si aplica)
   */
  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener permisos agrupados por módulo
   */
  public function groupedByModule()
  {
    try {
      return $this->success($this->service->getPermissionsGroupedByModule());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener permisos de un módulo específico
   */
  public function byModule(Request $request)
  {
    try {
      $module = $request->input('module');
      return $this->success($this->service->getPermissionsByModule($module));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtener permisos por tipo
   */
  public function byType(Request $request)
  {
    try {
      $type = $request->input('type');
      return $this->success($this->service->getPermissionsByType($type));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Asignar permiso a un rol
   */
  public function assignToRole(StorePermissionRoleRequest $request)
  {
    try {
      return $this->success($this->service->assignPermissionToRole(
        $request->role_id,
        $request->permission_id,
        $request->granted ?? true
      ));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Asignar múltiples permisos a un rol
   */
  public function assignMultipleToRole(StoreMultiplePermissionRoleRequest $request)
  {
    try {
      return $this->success($this->service->assignMultiplePermissionsToRole(
        $request->role_id,
        $request->permissions
      ));
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
   * Activar/desactivar permiso
   */
  public function toggleActive($id)
  {
    try {
      $this->service->toggleActive($id);
      return $this->success(['message' => 'Estado cambiado correctamente']);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
