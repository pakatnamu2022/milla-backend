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
   * @param string|null $date Fecha en formato 'Y-m-d'. Si es null, usa la fecha de hoy.
   * @return array|null Retorna los datos insertados o null si no hay tasa de cambio.
   */
  public function syncExchangeRate(): ?array
  {
    $date = now()->format('Y-m-d');

    // Get currency IDs for PEN and USD
    $fromCurrencyId = TypeCurrency::where('code', 'PEN')->first()->id;
    $toCurrencyId = TypeCurrency::where('code', 'USD')->first()->id;

    if (!$fromCurrencyId || !$toCurrencyId) {
      throw new \Exception('No se encontraron las monedas PEN o USD.');
    }

    // If the exchange rate for the given date already exists, do not insert again
    $exists = ExchangeRate::where('from_currency_id', $fromCurrencyId)
      ->where('to_currency_id', $toCurrencyId)
      ->where('type', 'VENTA')
      ->where('date', $date)
      ->exists();

    if (!$exists) {
//      Call the stored procedure to get the exchange rate
      $result = DB::connection('dbtp3')->select(
        "EXEC dbo.ERP_SP_MC_TasaCambio_Vender @Fecha = '{$date}'"
      );
//      If the result is not empty, insert the exchange rate into the local database
      if (!empty($result)) {
        $exchangeRate = $result[0]->TasaCambio ?? null;
//        If there is an exchange rate, insert it into the local database
        if ($exchangeRate) {
//        Insert the exchange rate into the local database
          $data = [
            'from_currency_id' => $fromCurrencyId,
            'to_currency_id' => $toCurrencyId,
            'type' => 'VENTA',
            'date' => $date,
            'rate' => $exchangeRate,
          ];

          ExchangeRate::create($data);
        }

        return $data;
      }
    }

    return null;
  }
}
