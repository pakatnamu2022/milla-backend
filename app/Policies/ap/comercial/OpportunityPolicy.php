<?php

namespace App\Policies\ap\comercial;

use App\Models\User;
use App\Policies\BasePolicy;

class OpportunityPolicy extends BasePolicy
{

  /**
   * Módulo/vista para verificar permisos
   */
  protected string $module = 'opportunity';

  public function myOpportunities(User $user): bool
  {
    return $this->hasPermission($user, 'view_all_users');
  }
}
