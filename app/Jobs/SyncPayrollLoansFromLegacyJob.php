<?php

namespace App\Jobs;

use App\Http\Services\gp\gestionhumana\payroll\PayrollLoanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncPayrollLoansFromLegacyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 0;

    public function __construct()
    {
        $this->onQueue('payroll-loans-sync');
    }

    public function handle(PayrollLoanService $service): void
    {
        $result = $service->syncFromLegacy();

        Log::info('Sincronización de préstamos legacy completada', $result);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Sincronización de préstamos legacy falló', ['error' => $exception->getMessage()]);
    }
}
