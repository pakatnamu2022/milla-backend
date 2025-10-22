<?php

namespace App\Policies\ap\comercial;

use App\Models\User;
use App\Policies\BasePolicy;

class PotentialBuyersPolicy extends BasePolicy
{
  /**
   * Módulo/vista para verificar permisos
   */
  protected string $module = 'opportunity';

  /**
   * Determina si el usuario puede ver oportunidades de todos los usuarios
   * Usa el permiso granular: opportunity.view_all_users
   */
  public function viewAllUsers(User $user): bool
  {
    return $this->hasPermission($user, 'view_all_users');
  }
}
