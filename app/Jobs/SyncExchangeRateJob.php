<?php

namespace App\Jobs;

use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * php artisan app:sync-exchange-rate
 */
class SyncExchangeRateJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(ExchangeRateService $exchangeRateService): void
  {
    try {
      $exchangeRateService->syncExchangeRate();
    } catch (\Exception $e) {
      Log::error('Error al sincronizar tasa de cambio: ' . $e->getMessage());
    }
  }
}
