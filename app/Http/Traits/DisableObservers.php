<?php

namespace App\Http\Traits;

use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;

trait DisableObservers
{
    /**
     * Deshabilitar temporalmente los observers para evitar jobs masivos
     */
    protected function disableEvaluationObservers(): void
    {
        EvaluationPersonResult::unsetEventDispatcher();
        EvaluationPersonCompetenceDetail::unsetEventDispatcher();
        EvaluationPerson::unsetEventDispatcher();
    }

    /**
     * Rehabilitar los observers
     */
    protected function enableEvaluationObservers(): void
    {
        EvaluationPersonResult::setEventDispatcher(app('events'));
        EvaluationPersonCompetenceDetail::setEventDispatcher(app('events'));
        EvaluationPerson::setEventDispatcher(app('events'));
    }

    /**
     * Ejecutar código sin observers y dispara un solo job al final
     */
    protected function withoutObservers(callable $callback, $evaluationId = null): mixed
    {
        $this->disableEvaluationObservers();

        try {
            $result = $callback();

            // Disparar un solo job al final si se proporciona evaluation_id
            if ($evaluationId) {
                \App\Jobs\UpdateEvaluationDashboards::dispatch($evaluationId)->onQueue('evaluation-dashboards');
            }

            return $result;
        } finally {
            $this->enableEvaluationObservers();
        }
    }
}