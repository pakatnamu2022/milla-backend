<?php

namespace App\Console\Commands;

use App\Jobs\SyncAccountsPayableJob;
use Illuminate\Console\Command;

class SyncAccountsPayableCommand extends Command
{
  protected $signature = 'ap:sync-payable';

  protected $description = 'Dispatch SyncAccountsPayableJob to sync accounts payable from SP_GP_ReporteDocumentosNoAplicadosCuentaPorPagar';

  public function handle(): int
  {
    $this->info('Dispatching SyncAccountsPayableJob...');
    SyncAccountsPayableJob::dispatch();
    $this->info('Job dispatched.');

    return Command::SUCCESS;
  }
}
