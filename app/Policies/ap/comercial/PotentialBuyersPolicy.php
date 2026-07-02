<?php

namespace App\Policies\ap\comercial;

use App\Models\User;
use App\Policies\BasePolicy;

class PotentialBuyersPolicy extends BasePolicy
{
  /**
   * Módulo/vista para verificar permisos
   */
  protected string $module = 'dashboard-equipo-leads';

  public function viewAdvisors(User $user): bool
  {
    return $this->hasPermission($user, 'viewAdvisors');
  }

  public function assign(User $user): bool
  {
    return $this->hasPermission($user, 'assign');
  }

  public function viewExternal(User $user): bool
  {
    return $this->hasPermission($user, 'viewExternal');
  }
}
