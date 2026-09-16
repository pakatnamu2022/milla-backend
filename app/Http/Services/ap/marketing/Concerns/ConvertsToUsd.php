<?php

namespace App\Http\Services\ap\marketing\Concerns;

use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\maestroGeneral\ExchangeRate;

/**
 * Convierte montos en distintas monedas a USD, para poder comparar
 * presupuestos y gastos del módulo de Marketing sin importar en qué
 * moneda se registró cada uno (mismo criterio que MktDashboardService).
 */
trait ConvertsToUsd
{
  private const USD_DEFAULT_EXCHANGE_RATE = 3.75;

  private array $usdRateCache = [];

  protected function toUsd(float $amount, ?int $currencyId, ?string $date = null): float
  {
    if (!$amount) {
      return 0.0;
    }
    if (!$currencyId || $currencyId === TypeCurrency::USD_ID) {
      return $amount;
    }
    $rate = $this->usdExchangeRate($date);
    return round($amount / $rate, 2);
  }

  private function usdExchangeRate(?string $date): float
  {
    $date = $date ?: now()->toDateString();

    if (isset($this->usdRateCache[$date])) {
      return $this->usdRateCache[$date];
    }

    $optimal = ExchangeRate::getOptimalExchangeRate($date, TypeCurrency::PEN_ID, TypeCurrency::USD_ID);
    $rate    = $optimal?->rate ? (float) $optimal->rate : self::USD_DEFAULT_EXCHANGE_RATE;

    return $this->usdRateCache[$date] = $rate;
  }
}
