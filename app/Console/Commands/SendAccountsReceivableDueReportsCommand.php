<?php

namespace App\Console\Commands;

use App\Jobs\SendDueAccountsReceivableReportsJob;
use Illuminate\Console\Command;

class SendAccountsReceivableDueReportsCommand extends Command
{
  protected $signature = 'ar:send-due-reports {--company=deposito : Company key (deposito)}';

  protected $description = 'Dispatch email reports for accounts receivable due within 2 days';

  public function handle(): int
  {
    $company = $this->option('company');

    $this->info("Dispatching SendDueAccountsReceivableReportsJob for company: {$company}");
    SendDueAccountsReceivableReportsJob::dispatch($company);
    $this->info('Job dispatched.');

    return Command::SUCCESS;
  }
}
