<?php

namespace App\Console\Commands;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseExpiredEvaluationsCommand extends Command
{
  protected $signature = 'evaluation:close-expired
                          {--dry-run : Mostrar qué se cerraría sin realizar cambios}';

  protected $description = 'Cierra automáticamente las evaluaciones cuyo end_date ya pasó';

  public function handle(): int
  {
    $dryRun = $this->option('dry-run');
    $today = Carbon::today('America/Lima');

    $expired = Evaluation::where('status', Evaluation::IN_PROGRESS_EVALUATION)
      ->whereDate('end_date', '<=', $today)
      ->get();

    if ($expired->isEmpty()) {
      $this->info('No hay evaluaciones activas vencidas.');
      return 0;
    }

    $this->info("Evaluaciones vencidas encontradas: {$expired->count()}");

    foreach ($expired as $evaluation) {
      $this->line("  - [{$evaluation->id}] {$evaluation->name} (end_date: {$evaluation->end_date})");

      if ($dryRun) {
        continue;
      }

      $evaluation->update(['status' => Evaluation::COMPLETED_EVALUATION]);
    }

    if ($dryRun) {
      $this->warn('Modo dry-run: no se realizaron cambios.');
    } else {
      $this->info("Se cerraron {$expired->count()} evaluación(es) correctamente.");
    }

    return 0;
  }
}
