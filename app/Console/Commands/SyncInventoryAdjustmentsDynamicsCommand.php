<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ValidatesPendingJobs;
use App\Jobs\SyncInventoryAdjustmentsDynamicsJob;
use Illuminate\Console\Command;

class SyncInventoryAdjustmentsDynamicsCommand extends Command
{
  use ValidatesPendingJobs;

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'inventory:sync-adjustments-dynamics';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sincroniza los ajustes de inventario desde Dynamics consultando el PA neIvConsultarAjustesInventario';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    // Validar límite de jobs pendientes antes de despachar
    if (!$this->canDispatchMoreJobs(SyncInventoryAdjustmentsDynamicsJob::class)) {
      return Command::SUCCESS;
    }

    $this->info('Despachando job para sincronizar ajustes de inventario desde Dynamics...');

    SyncInventoryAdjustmentsDynamicsJob::dispatch();

    $this->info('Job despachado exitosamente');
    $this->info('El job procesará los ajustes de inventario de POSTVENTA de los últimos 3 días');

    return Command::SUCCESS;
  }
}