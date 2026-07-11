<?php

namespace App\Models\gp\maestroGeneral;

use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends BaseModel
{
  use SoftDeletes;

  protected $table = 'exchange_rate';

  protected $fillable = [
    'from_currency_id',
    'to_currency_id',
    'type',
    'date',
    'rate',
  ];

  const filters = [
    'from_currency_id' => '=',
    'to_currency_id' => '=',
    'type' => '=',
    'date' => '=',
    'rate' => '=',
  ];

  const sorts = [
    'from_currency_id',
    'to_currency_id',
    'type',
    'date',
    'rate',
  ];

  const TYPE_VENTA = 'VENDER';
  const TYPE_NEGOCIADOR = 'NEGOCIADOR';


  public function fromCurrency()
  {
    return $this->belongsTo(TypeCurrency::class, 'from_currency_id');
  }

  public function toCurrency()
  {
    return $this->belongsTo(TypeCurrency::class, 'to_currency_id');
  }

  public static function todayUSD()
  {
    return self::where('from_currency_id', TypeCurrency::PEN_ID)
      ->where('to_currency_id', TypeCurrency::USD_ID)
      ->where('date', date('Y-m-d'))
      ->where('type', self::TYPE_VENTA)
      ->orderBy('created_at', 'desc')
      ->first();
  }

  /**
   * Obtiene el tipo de cambio más conveniente entre una fecha dada y la fecha actual.
   * Si el rate es igual en ambas fechas, retorna el de la fecha actual.
   *
   * @param string $date Fecha a comparar (formato Y-m-d). No puede ser futura.
   * @param int $fromCurrencyId ID de la moneda origen
   * @param int $toCurrencyId ID de la moneda destino
   * @param string $type Tipo de cambio (VENDER o NEGOCIADOR)
   * @param bool $preferLower Si es true, prefiere el rate más bajo (conveniente para comprar). Si es false, prefiere el más alto (conveniente para vender).
   * @return self|null Retorna el modelo del tipo de cambio más conveniente
   * @throws \Exception Si la fecha es futura
   */
  public static function getOptimalExchangeRate(
    $date,
    $fromCurrencyId = null,
    $toCurrencyId = null,
    $type = null,
    $preferLower = true
  ) {
    // Valores por defecto: PEN a USD, tipo VENTA
    $fromCurrencyId = $fromCurrencyId ?? TypeCurrency::PEN_ID;
    $toCurrencyId = $toCurrencyId ?? TypeCurrency::USD_ID;
    $type = $type ?? self::TYPE_VENTA;

    // Validar que la fecha no sea futura
    $inputDate = date('Y-m-d', strtotime($date));
    $today = date('Y-m-d');

    if ($inputDate > $today) {
      throw new \Exception('La fecha no puede ser futura');
    }

    // Obtener el tipo de cambio de la fecha enviada
    $exchangeRateFromDate = self::where('from_currency_id', $fromCurrencyId)
      ->where('to_currency_id', $toCurrencyId)
      ->where('date', $inputDate)
      ->where('type', $type)
      ->orderBy('created_at', 'desc')
      ->first();

    // Obtener el tipo de cambio actual
    $exchangeRateToday = self::where('from_currency_id', $fromCurrencyId)
      ->where('to_currency_id', $toCurrencyId)
      ->where('date', $today)
      ->where('type', $type)
      ->orderBy('created_at', 'desc')
      ->first();

    // Si no existe el tipo de cambio de la fecha enviada, retornar el actual
    if (!$exchangeRateFromDate) {
      return $exchangeRateToday;
    }

    // Si no existe el tipo de cambio actual, retornar el de la fecha enviada
    if (!$exchangeRateToday) {
      return $exchangeRateFromDate;
    }

    // Si ambos rates son iguales, quedarse con el actual
    if ($exchangeRateFromDate->rate == $exchangeRateToday->rate) {
      return $exchangeRateToday;
    }

    // Determinar cuál conviene según preferencia
    if ($preferLower) {
      // Prefiere el rate más bajo (conveniente para comprar)
      return $exchangeRateFromDate->rate < $exchangeRateToday->rate
        ? $exchangeRateFromDate
        : $exchangeRateToday;
    } else {
      // Prefiere el rate más alto (conveniente para vender)
      return $exchangeRateFromDate->rate > $exchangeRateToday->rate
        ? $exchangeRateFromDate
        : $exchangeRateToday;
    }
  }
}
