<?php

namespace App\Http\Controllers\gp\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Services\gp\maestroGeneral\ExchangeRateService;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExchangeRateController extends Controller
{
  protected ExchangeRateService $service;

  public function __construct(ExchangeRateService $service)
  {
    $this->service = $service;
  }

  /**
   * Obtiene el tipo de cambio según moneda y fecha
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getByDateAndCurrency(Request $request)
  {
    $request->validate([
      'to_currency_id' => 'required|integer|exists:type_currency,id',
      'date' => 'required|date_format:Y-m-d',
      'type' => 'sometimes|in:VENDER,NEGOCIADOR'
    ]);

    $exchangeRate = $this->service->getExchangeRate(
      $request->to_currency_id,
      $request->date,
      $request->type ?? ExchangeRate::TYPE_VENTA
    );

    if (!$exchangeRate) {
      return response()->json([
        'message' => 'No se encontró tipo de cambio para los parámetros proporcionados'
      ], Response::HTTP_NOT_FOUND);
    }

    return response()->json([
      'data' => [
        'id' => $exchangeRate->id,
        'type' => $exchangeRate->type,
        'date' => $exchangeRate->date,
        'rate' => $exchangeRate->rate,
      ]
    ]);
  }
}
