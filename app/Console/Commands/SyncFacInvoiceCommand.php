<?php

namespace App\Console\Commands;

use App\Jobs\SyncFacInvoiceJob;
use Illuminate\Console\Command;

class SyncFacInvoiceCommand extends Command
{
  protected $signature = 'tp:sync-fac-invoice';

  protected $description = 'Sincroniza registros de RM20101_MILLA_DOCFV (DBTP2) hacia fac_invoice';

  public function handle(): int
  {
    $this->info('Despachando SyncFacInvoiceJob...');

    SyncFacInvoiceJob::dispatch();

    $this->info('Job despachado exitosamente');

    return Command::SUCCESS;
  }
}
