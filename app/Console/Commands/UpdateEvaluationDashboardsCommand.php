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
  protected $signature = 'evaluation:update-dashboards {evaluation_id?} {--sync : Ejecutar sincronamente en lugar de usar cola}';

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
    $runSync = $this->option('sync');

    if ($runSync) {
      // Ejecutar sincronamente
      if ($evaluationId) {
        $this->info("Actualizando dashboard para evaluación ID: {$evaluationId} (síncrono)");
        UpdateEvaluationDashboards::dispatchSync($evaluationId);
      } else {
        $this->info("Actualizando dashboards para todas las evaluaciones (síncrono)");
        UpdateEvaluationDashboards::dispatchSync();
      }
      $this->info("Job de actualización completado correctamente.");
    } else {
      // Ejecutar asíncrono en cola
      if ($evaluationId) {
        $this->info("Enviando job para evaluación ID: {$evaluationId} a cola 'evaluation-dashboards'");
        UpdateEvaluationDashboards::dispatch($evaluationId)->onQueue('evaluation-dashboards');
      } else {
        $this->info("Enviando job para todas las evaluaciones a cola 'evaluation-dashboards'");
        UpdateEvaluationDashboards::dispatch()->onQueue('evaluation-dashboards');
      }
      $this->info("Job enviado a la cola correctamente. Ejecute 'php artisan queue:work' para procesarlo.");
    }

    return 0;
  }
}
