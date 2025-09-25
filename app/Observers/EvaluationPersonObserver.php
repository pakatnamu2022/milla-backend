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
        UpdateEvaluationDashboards::dispatch($evaluationPerson->evaluation_id)->onQueue('evaluation-dashboards');
    }

    /**
     * Handle the EvaluationPerson "updated" event.
     */
    public function updated(EvaluationPerson $evaluationPerson): void
    {
        // Solo actualizar si cambió algo relevante para los cálculos
        if ($evaluationPerson->wasChanged(['result', 'qualification', 'wasEvaluated'])) {
            UpdateEvaluationDashboards::dispatch($evaluationPerson->evaluation_id)->onQueue('evaluation-dashboards');
        }
    }

    /**
     * Handle the EvaluationPerson "deleted" event.
     */
    public function deleted(EvaluationPerson $evaluationPerson): void
    {
        UpdateEvaluationDashboards::dispatch($evaluationPerson->evaluation_id)->onQueue('evaluation-dashboards');
    }
}
