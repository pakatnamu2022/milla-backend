<?php

namespace App\Jobs;

use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
      $result = $exchangeRateService->syncExchangeRate();

      if ($result) {
        // Log::info('Tasa de cambio sincronizada correctamente', $result);
      } else {
        // Log::info('No se encontró tasa de cambio para sincronizar o ya existe');
      }
    } catch (\Exception $e) {
      // Log::error('Error al sincronizar tasa de cambio: ' . $e->getMessage());
    }
  }
}
