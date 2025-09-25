<?php

namespace App\Observers;

use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Jobs\UpdateEvaluationDashboards;

class EvaluationPersonObserver
{
    /**
     * Handle the EvaluationPerson "created" event.
     */
    public function created(EvaluationPerson $evaluationPerson): void
    {
        UpdateEvaluationDashboards::dispatchSync($evaluationPerson->evaluation_id);
    }

    /**
     * Handle the EvaluationPerson "updated" event.
     */
    public function updated(EvaluationPerson $evaluationPerson): void
    {
        // Solo actualizar si cambió algo relevante para los cálculos
        if ($evaluationPerson->wasChanged(['result', 'qualification', 'wasEvaluated'])) {
            UpdateEvaluationDashboards::dispatchSync($evaluationPerson->evaluation_id);
        }
    }

    /**
     * Handle the EvaluationPerson "deleted" event.
     */
    public function deleted(EvaluationPerson $evaluationPerson): void
    {
        UpdateEvaluationDashboards::dispatchSync($evaluationPerson->evaluation_id);
    }
}
