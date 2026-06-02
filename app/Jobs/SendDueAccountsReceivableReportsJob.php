<?php

namespace App\Jobs;

use App\Http\Services\dp\comercial\AccountsReceivableService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendDueAccountsReceivableReportsJob implements ShouldQueue
{
  use Queueable;

  public int $tries   = 1;
  public int $timeout = 600;

  public function __construct(public readonly string $company = 'deposito')
  {
    $this->onQueue('accounts-receivable-reports');
  }

  public function handle(AccountsReceivableService $service): void
  {
    $result = $service->sendDueSedeReports($this->company);
    Log::info('SendDueAccountsReceivableReportsJob', $result);
  }

  public function failed(\Throwable $exception): void
  {
    Log::error("SendDueAccountsReceivableReportsJob [{$this->company}]: {$exception->getMessage()}");
  }
}
