<?php

namespace App\Observers;

use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Jobs\UpdateEvaluationDashboards;

class EvaluationPersonCompetenceDetailObserver
{
    /**
     * Handle the EvaluationPersonCompetenceDetail "created" event.
     */
    public function created(EvaluationPersonCompetenceDetail $competenceDetail): void
    {
        UpdateEvaluationDashboards::dispatch($competenceDetail->evaluation_id)->onQueue('evaluation-dashboards');
    }

    /**
     * Handle the EvaluationPersonCompetenceDetail "updated" event.
     */
    public function updated(EvaluationPersonCompetenceDetail $competenceDetail): void
    {
        // Solo actualizar si cambió el resultado
        if ($competenceDetail->wasChanged(['result'])) {
            UpdateEvaluationDashboards::dispatch($competenceDetail->evaluation_id)->onQueue('evaluation-dashboards');
        }
    }

    /**
     * Handle the EvaluationPersonCompetenceDetail "deleted" event.
     */
    public function deleted(EvaluationPersonCompetenceDetail $competenceDetail): void
    {
        UpdateEvaluationDashboards::dispatch($competenceDetail->evaluation_id)->onQueue('evaluation-dashboards');
    }
}
