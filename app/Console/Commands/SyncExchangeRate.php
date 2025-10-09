<?php

namespace App\Console\Commands;

use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use Illuminate\Console\Command;

class SyncExchangeRate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:sync-exchange-rate';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sincroniza la tasa de cambio desde la base dbtp3 a la tabla exchange_rate para la fecha actual';

  /**
   * Execute the console command.
   */
  public function handle(ExchangeRateService $exchangeRateService)
  {
    $fecha = now()->format('Y-m-d');
    $this->info("Sincronizando tasa de cambio para la fecha: {$fecha}...");

    try {
      $result = $exchangeRateService->syncExchangeRate();

      switch ($result['status']) {
        case 'success':
          $this->info("✅ Tasa de cambio sincronizada correctamente: {$result['data']['rate']}");
          return Command::SUCCESS;

        case 'already-exists':
          $this->warn("ℹ️ Ya existe una tasa de cambio registrada para la fecha {$fecha}.");
          return Command::SUCCESS;

        case 'no-data':
          $this->warn("⚠️ No se encontró tasa de cambio en la base remota para la fecha {$fecha}.");
          return Command::FAILURE;

        case 'error':
          $this->error("❌ Error: {$result['message']}");
          return Command::FAILURE;

        default:
          $this->warn("⚠️ Resultado inesperado al sincronizar tasa de cambio.");
          return Command::FAILURE;
      }

    } catch (\Throwable $e) {
      $this->error("❌ Excepción no controlada: " . $e->getMessage());
      $this->error($e->getTraceAsString());
      return Command::FAILURE;
    }
  }
}
