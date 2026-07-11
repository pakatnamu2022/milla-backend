<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Única fuente de verdad para resolver el tipo de cambio a USD de modelos que
 * se cotizan/facturan en soles pero necesitan reportarse en dólares (OT y
 * cotizaciones de posventa).
 *
 * Orden de prioridad: moneda ya en USD > exchange_rate propio > relación
 * exchangeRate propia > tipo de cambio del último documento electrónico
 * emitido con exchange_rate_id > valor por defecto.
 */
trait HasExchangeRateToUsd
{
  /**
   * Tipo de cambio por defecto (PEN a USD aproximado) cuando no hay ninguna
   * otra fuente disponible.
   */
  private const DEFAULT_EXCHANGE_RATE_TO_USD = 3.75;

  /**
   * Documentos electrónicos (facturas/boletas/notas) asociados a este modelo,
   * usados como última fuente del tipo de cambio cuando el modelo no tiene
   * uno propio asignado.
   */
  abstract public function exchangeRateDocuments(): HasMany;

  public function getExchangeRateToUsd(): float
  {
    if ($this->typeCurrency && $this->typeCurrency->code === 'USD') {
      return 1.0;
    }

    if ($this->exchange_rate) {
      return (float) $this->exchange_rate;
    }

    if ($this->exchangeRate && $this->exchangeRate->rate) {
      return (float) $this->exchangeRate->rate;
    }

    $lastDocument = $this->exchangeRateDocuments()
      ->whereNotNull('exchange_rate_id')
      ->orderByDesc('created_at')
      ->first();

    if ($lastDocument && $lastDocument->exchangeRate && $lastDocument->exchangeRate->rate) {
      return (float) $lastDocument->exchangeRate->rate;
    }

    return self::DEFAULT_EXCHANGE_RATE_TO_USD;
  }
}