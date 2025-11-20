<?php

namespace App\Http\Services\gp\maestroGeneral;

use App\Http\Services\BaseService;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Illuminate\Support\Facades\DB;

class ExchangeRateService extends BaseService
{
  /**
   * Sincroniza la tasa de cambio desde la base dbtp3 y la guarda en exchange_rate.
   *
   * @return array
   *
   * Estructura:
   * [
   *   'status' => 'success'|'no-data'|'already-exists'|'error',
   *   'data' => [...], // si success
   *   'message' => '...'
   * ]
   */
  public function syncExchangeRate(): array
  {
    $date = now()->format('Y-m-d');

    try {
      $fromCurrency = TypeCurrency::where('code', 'PEN')->first();
      $toCurrency = TypeCurrency::where('code', 'USD')->first();

      if (!$fromCurrency || !$toCurrency) {
        return [
          'status' => 'error',
          'message' => 'No se encontraron las monedas PEN o USD en la tabla type_currency.'
        ];
      }

      $exists = ExchangeRate::where('from_currency_id', $fromCurrency->id)
        ->where('to_currency_id', $toCurrency->id)
        ->where('type', ExchangeRate::TYPE_VENTA)
        ->where('date', $date)
        ->exists();

      if ($exists) {
        return [
          'status' => 'already-exists',
          'message' => 'La tasa ya estaba registrada para esta fecha.'
        ];
      }

      $result = DB::connection('dbtp3')->select(
        "EXEC dbo.ERP_SP_MC_TasaCambio_Vender @Fecha = ?", [$date]
      );

      if (empty($result)) {
        return [
          'status' => 'no-data',
          'message' => "No se encontró tasa de cambio en la base remota para la fecha {$date}."
        ];
      }

      $exchangeRate = $result[0]->TasaCambio ?? null;

      if (!$exchangeRate) {
        return [
          'status' => 'no-data',
          'message' => "El SP no devolvió un valor de TasaCambio para la fecha {$date}."
        ];
      }

      $data = [
        'from_currency_id' => $fromCurrency->id,
        'to_currency_id' => $toCurrency->id,
        'type' => ExchangeRate::TYPE_VENTA,
        'date' => $date,
        'rate' => $exchangeRate,
      ];

      ExchangeRate::create($data);

      return [
        'status' => 'success',
        'data' => $data,
        'message' => 'Tasa de cambio insertada correctamente.'
      ];
    } catch (\Throwable $e) {
      return [
        'status' => 'error',
        'message' => $e->getMessage()
      ];
    }
  }

  public function getCurrentUSDRate()
  {
    $exchangeRate = ExchangeRate::todayUSD();
    if (!$exchangeRate) {
      throw new \Exception('No se ha registrado la tasa de cambio para hoy.');
    }
    return $exchangeRate;
  }

  /**
   * Obtiene el tipo de cambio según la moneda origen, moneda destino y fecha
   *
   * @param int $toCurrencyId
   * @param string $date (formato: Y-m-d)
   * @param string $type (VENDER o NEGOCIADOR, por defecto VENDER)
   * @return ExchangeRate|null
   */
  public function getExchangeRate(int $toCurrencyId, string $date, string $type = ExchangeRate::TYPE_VENTA)
  {
    return ExchangeRate::where('to_currency_id', $toCurrencyId)
      ->where('date', $date)
      ->where('type', $type)
      ->orderBy('created_at', 'desc')
      ->first();
  }
}
