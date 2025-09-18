<?php

namespace App\Policies\gp\gestionsistema;

use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;

class SedePolicy
{
  /**
   * Determine whether the user can view any models.
   */
  public function viewAny(User $user): bool
  {
    return false;
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, Sede $sede): bool
  {
    return false;
  }

  /**
   * Determine whether the user can create models.
   */
  public function create(User $user): bool
  {
    return false;
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(User $user, \App\Models\gp\maestroGeneral\Sede $sede): bool
  {
    return false;
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(User $user, \App\Models\gp\maestroGeneral\Sede $sede): bool
  {
    return false;
  }

  /**
   * Determine whether the user can restore the model.
   */
  public function restore(User $user, Sede $sede): bool
  {
    return false;
  }

  /**
   * Determine whether the user can permanently delete the model.
   */
  public function forceDelete(User $user, Sede $sede): bool
  {
    return false;
  }
}
