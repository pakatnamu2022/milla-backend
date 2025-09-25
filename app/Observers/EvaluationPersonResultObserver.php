<?php

namespace App\Observers;

use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Jobs\UpdateEvaluationDashboards;

class EvaluationPersonResultObserver
{
    /**
     * Handle the EvaluationPersonResult "created" event.
     */
    public function created(EvaluationPersonResult $evaluationPersonResult): void
    {
        UpdateEvaluationDashboards::dispatchSync($evaluationPersonResult->evaluation_id);
    }

    /**
     * Handle the EvaluationPersonResult "updated" event.
     */
    public function updated(EvaluationPersonResult $evaluationPersonResult): void
    {
        // Solo actualizar si cambió algo relevante para los cálculos
        if ($evaluationPersonResult->wasChanged(['result', 'objectivesResult', 'competencesResult', 'status'])) {
            UpdateEvaluationDashboards::dispatchSync($evaluationPersonResult->evaluation_id);
        }
    }

    /**
     * Handle the EvaluationPersonResult "deleted" event.
     */
    public function deleted(EvaluationPersonResult $evaluationPersonResult): void
    {
        UpdateEvaluationDashboards::dispatchSync($evaluationPersonResult->evaluation_id);
    }
}
