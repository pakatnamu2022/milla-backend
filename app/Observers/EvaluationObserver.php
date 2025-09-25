<?php

namespace App\Observers;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Jobs\UpdateEvaluationDashboards;

class EvaluationObserver
{
    /**
     * Handle the Evaluation "created" event.
     */
    public function created(Evaluation $evaluation): void
    {
        // Crear dashboards para nueva evaluación
        UpdateEvaluationDashboards::dispatch($evaluation->id)->onQueue('evaluation-dashboards');
    }

    /**
     * Handle the Evaluation "updated" event.
     */
    public function updated(Evaluation $evaluation): void
    {
        // Solo actualizar si cambió algo relevante para los cálculos
        if ($evaluation->wasChanged(['status', 'objectivesPercentage', 'competencesPercentage', 'start_date', 'end_date'])) {
            UpdateEvaluationDashboards::dispatch($evaluation->id)->onQueue('evaluation-dashboards');
        }
    }

    /**
     * Handle the Evaluation "deleted" event.
     */
    public function deleted(Evaluation $evaluation): void
    {
        // Los dashboards se eliminarán automáticamente por cascade
    }
}
