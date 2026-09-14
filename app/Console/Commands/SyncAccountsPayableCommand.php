<?php

namespace App\Console\Commands;

use App\Jobs\SyncAccountsPayableJob;
use Illuminate\Console\Command;

class SyncAccountsPayableCommand extends Command
{
  protected $signature = 'ap:sync-payable {--company= : deposito|automotores, ambas si se omite}';

  protected $description = 'Dispatch SyncAccountsPayableJob to sync accounts payable from SP_GP_ReporteDocumentosNoAplicadosCuentaPorPagar';

  public function handle(): int
  {
    $companies = $this->option('company') ? [$this->option('company')] : ['deposito', 'automotores'];

    foreach ($companies as $company) {
      $this->info("Dispatching SyncAccountsPayableJob ({$company})...");
      SyncAccountsPayableJob::dispatch($company);
    }
    $this->info('Job(s) dispatched.');

    return Command::SUCCESS;
  }
}
