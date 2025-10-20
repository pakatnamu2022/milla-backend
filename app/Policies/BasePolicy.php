<?php

namespace App\Policies;

use App\Models\User;

/**
 * BasePolicy - Clase base para todas las Policies
 *
 * Proporciona métodos comunes para validar permisos básicos (CRUD)
 * y permisos granulares combinando Access y Permission
 */
abstract class BasePolicy
{
    /**
     * Nombre del módulo/vista para verificar permisos
     * Debe ser sobrescrito en cada Policy hija
     *
     * @var string
     */
    protected string $module;

    /**
     * Verifica si el usuario puede listar registros (index)
     * Usa el permiso básico 'ver' de config_asigxvistaxrole
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAccessToView($this->module, 'ver');
    }

    /**
     * Verifica si el usuario puede ver un registro específico (show)
     * Usa el permiso básico 'ver' de config_asigxvistaxrole
     */
    public function view(User $user, $model): bool
    {
        return $user->hasAccessToView($this->module, 'ver');
    }

    /**
     * Verifica si el usuario puede crear registros (store)
     * Usa el permiso básico 'crear' de config_asigxvistaxrole
     */
    public function create(User $user): bool
    {
        return $user->hasAccessToView($this->module, 'crear');
    }

    /**
     * Verifica si el usuario puede actualizar registros (update)
     * Usa el permiso básico 'editar' de config_asigxvistaxrole
     */
    public function update(User $user, $model): bool
    {
        return $user->hasAccessToView($this->module, 'editar');
    }

    /**
     * Verifica si el usuario puede eliminar/anular registros (delete/destroy)
     * Usa el permiso básico 'anular' de config_asigxvistaxrole
     */
    public function delete(User $user, $model): bool
    {
        return $user->hasAccessToView($this->module, 'anular');
    }

    /**
     * Verifica si el usuario puede restaurar registros
     * Por defecto usa el permiso 'editar', puede ser sobrescrito
     */
    public function restore(User $user, $model): bool
    {
        return $user->hasAccessToView($this->module, 'editar');
    }

    /**
     * Verifica si el usuario puede eliminar permanentemente
     * Por defecto usa el permiso 'anular', puede ser sobrescrito
     */
    public function forceDelete(User $user, $model): bool
    {
        return $user->hasAccessToView($this->module, 'anular');
    }

    /**
     * Helper: Verifica permiso granular específico
     * Usa la tabla permission
     *
     * @param User $user
     * @param string $action Nombre de la acción (ej: 'export', 'approve', 'resend')
     * @return bool
     */
    protected function hasPermission(User $user, string $action): bool
    {
        $permissionCode = "{$this->module}.{$action}";
        return $user->hasPermission($permissionCode);
    }

    /**
     * Helper: Verifica si el usuario tiene al menos uno de los permisos
     *
     * @param User $user
     * @param array $actions Array de acciones
     * @return bool
     */
    protected function hasAnyPermission(User $user, array $actions): bool
    {
        foreach ($actions as $action) {
            if ($this->hasPermission($user, $action)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper: Verifica si el usuario tiene todos los permisos
     *
     * @param User $user
     * @param array $actions Array de acciones
     * @return bool
     */
    protected function hasAllPermissions(User $user, array $actions): bool
    {
        foreach ($actions as $action) {
            if (!$this->hasPermission($user, $action)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper: Combina permiso básico + granular
     * Útil para acciones que requieren ambos tipos de permiso
     *
     * @param User $user
     * @param string $basicAction 'crear', 'ver', 'editar', 'anular'
     * @param string $granularAction Nombre de la acción granular
     * @return bool
     */
    protected function hasBasicAndGranularPermission(User $user, string $basicAction, string $granularAction): bool
    {
        return $user->hasAccessToView($this->module, $basicAction)
            && $this->hasPermission($user, $granularAction);
    }
}
