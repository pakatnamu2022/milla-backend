<?php

namespace App\Policies\gp\gestionhumana\evaluacion;

use App\Models\gp\gestionhumana\evaluacion\EvaluationMetric;
use App\Models\gp\gestionsistema\User;

class EvaluationMetricPolicy
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
    public function view(User $user, EvaluationMetric $evaluationMetric): bool
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
    public function update(User $user, EvaluationMetric $evaluationMetric): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EvaluationMetric $evaluationMetric): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EvaluationMetric $evaluationMetric): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EvaluationMetric $evaluationMetric): bool
    {
        return false;
    }
}
