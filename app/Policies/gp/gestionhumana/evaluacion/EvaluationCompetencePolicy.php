<?php

namespace App\Policies\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\evaluacion\EvaluationCompetence;
use App\Models\gp\gestionsistema\User;

class EvaluationCompetencePolicy
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
    public function view(User $user, EvaluationCompetence $evaluationCompetence): bool
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
    public function update(User $user, EvaluationCompetence $evaluationCompetence): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EvaluationCompetence $evaluationCompetence): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EvaluationCompetence $evaluationCompetence): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EvaluationCompetence $evaluationCompetence): bool
    {
        return false;
    }
}
