<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\PermissionResource;
use App\Http\Resources\gp\gestionsistema\ViewResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Permission;
use App\Models\gp\gestionsistema\RolePermission;
use App\Models\gp\gestionsistema\View;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ViewService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      View::where('status_deleted', 1),
      $request,
      View::filters,
      View::sorts,
      ViewResource::class,
    );
  }

  private function enrichViewData(array $data): array
  {
    $data['slug'] = !empty($data['descripcion']) ? Str::slug($data['descripcion']) : null;
    return $data;
  }

  public function find($id)
  {
    $view = View::where('id', $id)
      ->where('status_deleted', 1)->first();
    if (!$view) {
      throw new Exception('Vista no encontrada');
    }
    return $view;
  }

  public function store($data)
  {
    $data = $this->enrichViewData($data);
    $view = View::create($data);
    return new ViewResource($view);
  }

  public function show($id)
  {
    return new ViewResource($this->find($id));
  }

  public function update($data)
  {
    $view = $this->find($data['id']);

    // Detectar si el route cambió
    $oldRoute = $view->route;
    $newRoute = $data['route'] ?? $oldRoute;

    // Si el route cambió, actualizar los permisos relacionados
    if ($oldRoute !== $newRoute && !empty($oldRoute) && !empty($newRoute)) {
      $this->updateRelatedPermissions($oldRoute, $newRoute, $view->id);
    }

    $data = $this->enrichViewData($data);
    $view->update($data);
    return new ViewResource($view);
  }

  /**
   * Actualiza los permisos relacionados cuando el route de una vista cambia
   *
   * @param string $oldRoute El route anterior (ej: "orden-compra-producto")
   * @param string $newRoute El nuevo route (ej: "factura-compra")
   * @param int $vistaId El ID de la vista
   */
  private function updateRelatedPermissions(string $oldRoute, string $newRoute, int $vistaId): void
  {
    // Buscar todos los permisos que empiecen con el route antiguo
    $permissions = Permission::where('vista_id', $vistaId)
      ->where('code', 'like', $oldRoute . '.%')
      ->get();

    foreach ($permissions as $permission) {
      // Extraer la acción del código (ej: "orden-compra-producto.view" -> "view")
      $parts = explode('.', $permission->code);
      $action = end($parts);

      // Crear el nuevo código con el nuevo route
      $newCode = $newRoute . '.' . $action;

      // Actualizar el código del permiso
      $permission->code = $newCode;

      // Actualizar el módulo también (algunos sistemas lo usan)
      $permission->module = $newRoute;

      $permission->save();
    }
  }

  public function destroy($id)
  {
    $view = $this->find($id);
    $view->status_deleted = 0;
    $view->save();
    return response()->json(['message' => 'Vista eliminada correctamente']);
  }

  public function duplicate($id)
  {
    $view = $this->find($id);
    $duplicated = $this->duplicateView($view, $view->parent_id);
    return new ViewResource($duplicated);
  }

  private function duplicateView(View $view, $parentId): View
  {
    $suffix = ' Copia';
    $routeSuffix = '-copia';

    $newView = View::create([
      'descripcion' => $view->descripcion . $suffix,
      'slug'        => $view->slug ? $view->slug . $routeSuffix : null,
      'route'       => $view->route ? $view->route . $routeSuffix : null,
      'ruta'        => $view->ruta ? $view->ruta . $routeSuffix : null,
      'icono'       => $view->icono,
      'icon'        => $view->icon,
      'submodule'   => $view->submodule,
      'company_id'  => $view->company_id,
      'parent_id'   => $parentId,
      'idPadre'     => $view->idPadre,
      'idSubPadre'  => $view->idSubPadre,
      'idHijo'      => $view->idHijo,
      'status_deleted' => 1,
    ]);

    // Duplicar permisos
    $oldRoute = $view->route;
    $newRoute = $newView->route;

    foreach ($view->permissions as $permission) {
      $newCode = ($oldRoute && $newRoute && str_starts_with($permission->code, $oldRoute . '.'))
        ? $newRoute . substr($permission->code, strlen($oldRoute))
        : $permission->code . $routeSuffix;

      Permission::create([
        'code'          => $newCode,
        'name'          => $permission->name,
        'description'   => $permission->description,
        'module'        => $newRoute ?? $permission->module,
        'vista_id'      => $newView->id,
        'policy_method' => $permission->policy_method,
        'is_active'     => $permission->is_active,
      ]);
    }

    // Duplicar hijos recursivamente
    foreach ($view->children()->where('status_deleted', 1)->get() as $child) {
      $this->duplicateView($child, $newView->id);
    }

    return $newView;
  }

  /**
   * Obtener vistas con sus permisos en formato árbol (sin paginación)
   */
  public function getViewsWithPermissions(Request $request)
  {
    $search = $request->get('search', null);
    $roleId = $request->get('rol_id', null);

    // Obtener permisos asignados al rol si se proporciona rol_id
    $rolePermissions = collect();
    if ($roleId) {
      $rolePermissions = RolePermission::where('role_id', $roleId)
        ->pluck('granted', 'permission_id');
    }

    $views = View::where('status_deleted', 1)
      ->where('descripcion', 'like', "%{$search}%")
      ->whereNotNull('route')
      ->with(['permissions' => function ($query) {
        $query->where('is_active', true)
          ->orderBy('code');
      }])
      ->orderBy('descripcion')
      ->get();

    return $views->map(function ($view) use ($rolePermissions) {
      $permissions = $view->permissions->map(function ($permission) use ($rolePermissions) {
        $resource = (new PermissionResource($permission))->resolve();
        $resource['is_assigned'] = $rolePermissions->has($permission->id);
        return $resource;
      });

      return [
        'id' => $view->id,
        'descripcion' => $view->descripcion,
        'slug' => $view->slug,
        'route' => $view->route,
        'icon' => $view->icon,
        'parent_id' => $view->parent_id,
        'company' => $view->company?->name ?? null,
        'padre' => $view->padre?->descripcion ?? null,
        'subPadre' => $view->subPadre?->descripcion ?? null,
        'hijo' => $view->hijo?->descripcion ?? null,
        'permissions' => $permissions,
      ];
    });
  }

//    GRAFICOS

  public function useStateGraph()
  {
    return View::query()
      ->selectRaw("estado_uso, COUNT(*) as total")
      ->where('status_deleted', 1)
      ->groupBy('estado_uso')
      ->get();
  }

  public function sedeGraph()
  {
    return View::selectRaw('companies.abbreviation as sede, COUNT(*) as total')
      ->join('config_sede', 'config_sede.id', '=', 'help_vistas.sede_id')
      ->join('companies', 'companies.id', '=', 'config_sede.empresa_id')
      ->where('help_vistas.status_deleted', 1)
      ->groupBy('companies.abbreviation')
      ->orderByDesc('total')
      ->get();
  }
}
