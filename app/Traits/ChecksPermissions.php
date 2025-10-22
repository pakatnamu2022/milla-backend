<?php

namespace App\Traits;

use App\Models\gp\gestionsistema\Permission;
use Illuminate\Support\Collection;

/**
 * Trait ChecksPermissions
 *
 * Proporciona métodos para verificar permisos granulares y básicos
 * Puede ser usado en User, Role, o Policies
 */
trait ChecksPermissions
{
    /**
     * Verifica si el usuario tiene un permiso granular específico
     * Busca en la tabla 'permission' a través de role_permission
     *
     * @param string $permissionCode Código del permiso (ej: 'vehicle_purchase_order.export')
     * @return bool
     */
    public function hasPermission(string $permissionCode): bool
    {
        // Si el modelo es User, buscar a través de sus roles
        if ($this instanceof \App\Models\User) {
            return $this->roles()
                ->whereHas('permissions', function($query) use ($permissionCode) {
                    $query->where('code', $permissionCode)
                          ->where('granted', true)
                          ->where('is_active', true);
                })
                ->exists();
        }

        // Si el modelo es Role, buscar directamente en sus permisos
        if ($this instanceof \App\Models\gp\gestionsistema\Role) {
            return $this->permissions()
                ->where('code', $permissionCode)
                ->where('granted', true)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene acceso básico (CRUD) a una vista
     * Busca en la tabla 'config_asigxvistaxrole' (Access)
     *
     * @param string $vistaSlug Slug de la vista (ej: 'vehicle_purchase_order')
     * @param string $action Acción: 'crear', 'ver', 'editar', 'anular'
     * @return bool
     */
    public function hasAccessToView(string $vistaSlug, string $action): bool
    {
        // Validar que la acción sea válida
        if (!in_array($action, ['crear', 'ver', 'editar', 'anular'])) {
            return false;
        }

        // Si el modelo es User, buscar a través de sus roles
        if ($this instanceof \App\Models\User) {
            return $this->roles()
                ->whereHas('accesses', function($query) use ($vistaSlug, $action) {
                    $query->whereHas('view', function($q) use ($vistaSlug) {
                        $q->where('slug', $vistaSlug);
                    })
                    ->where($action, true)
                    ->where('status_deleted', 1);
                })
                ->exists();
        }

        // Si el modelo es Role, buscar directamente en sus accesos
        if ($this instanceof \App\Models\gp\gestionsistema\Role) {
            return \App\Models\gp\gestionsistema\Access::where('role_id', $this->id)
                ->whereHas('view', function($q) use ($vistaSlug) {
                    $q->where('slug', $vistaSlug);
                })
                ->where($action, true)
                ->where('status_deleted', 1)
                ->exists();
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene ALGUNO de los permisos especificados
     *
     * @param array $permissions Array de códigos de permisos
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si el usuario tiene TODOS los permisos especificados
     *
     * @param array $permissions Array de códigos de permisos
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene todos los permisos granulares del usuario
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        if ($this instanceof \App\Models\User) {
            $roleIds = $this->roles->pluck('id');

            return Permission::whereHas('roles', function($query) use ($roleIds) {
                $query->whereIn('role_id', $roleIds)
                      ->where('granted', true);
            })
            ->where('is_active', true)
            ->get();
        }

        if ($this instanceof \App\Models\gp\gestionsistema\Role) {
            return $this->permissions()
                ->wherePivot('granted', true)
                ->where('is_active', true)
                ->get();
        }

        return collect();
    }

    /**
     * Obtiene todos los permisos de un módulo específico
     *
     * @param string $module Nombre del módulo
     * @return Collection
     */
    public function getPermissionsByModule(string $module): Collection
    {
        return $this->getAllPermissions()->where('module', $module);
    }

    /**
     * Verifica si el usuario puede realizar una acción básica (CRUD) en una vista
     * Es un alias más semántico de hasAccessToView
     *
     * @param string $vistaSlug Slug de la vista
     * @param string $action 'create', 'read', 'update', 'delete'
     * @return bool
     */
    public function canAccessView(string $vistaSlug, string $action): bool
    {
        // Mapear acciones en inglés a español
        $actionMap = [
            'create' => 'crear',
            'read' => 'ver',
            'view' => 'ver',
            'update' => 'editar',
            'edit' => 'editar',
            'delete' => 'anular',
            'destroy' => 'anular',
        ];

        $spanishAction = $actionMap[$action] ?? $action;

        return $this->hasAccessToView($vistaSlug, $spanishAction);
    }
}
