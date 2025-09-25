<?php

namespace App\Console\Commands;

use App\Jobs\UpdateEvaluationDashboards;
use Illuminate\Console\Command;

class UpdateEvaluationDashboardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluation:update-dashboards {evaluation_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los dashboards precalculados de evaluaciones. Si no se especifica ID, actualiza todas las evaluaciones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $evaluationId = $this->argument('evaluation_id');

        if ($evaluationId) {
            $this->info("Actualizando dashboard para evaluación ID: {$evaluationId}");
            UpdateEvaluationDashboards::dispatchSync($evaluationId);
        } else {
            $this->info("Actualizando dashboards para todas las evaluaciones");
            UpdateEvaluationDashboards::dispatchSync();
        }

        $this->info("Job de actualización completado correctamente.");

        return 0;
    }
}
