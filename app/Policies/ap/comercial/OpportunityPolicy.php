<?php

namespace App\Policies\ap\comercial;

use App\Models\User;
use App\Policies\BasePolicy;

class OpportunityPolicy extends BasePolicy
{
  /**
   * Módulo/vista para verificar permisos
   */
  protected string $module = 'agenda';

  /**
   * Determina si el usuario puede ver asesores en la agenda
   * Usa el permiso granular: agenda.viewAdvisors
   */
  public function viewAdvisors(User $user): bool
  {
    // Verificar permiso específico de agenda sin cambiar el módulo base
    $permissionCode = $this->module . '.viewAdvisors';
    return $user->hasPermission($permissionCode);
  }
}
