<?php

namespace App\Http\Resources\ap\maestroGeneral;

use App\Models\gp\maestroGeneral\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeCurrencyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'symbol' => $this->symbol,
      'area_id' => $this->area_id,
      'status' => $this->status,
      'current_exchange_rate' => $this->getCurrentExchangeRate()
    ];
  }

  private function getCurrentExchangeRate()
  {
    switch ($this->code) {
      case 'PEN':
        return 1;

      case 'USD':
        $exchangeRate = ExchangeRate::todayUSD();
        return $exchangeRate ? $exchangeRate->rate : null;

      case 'EUR':
        return 0; // Temporal: función aún no desarrollada

      default:
        return null;
    }
  }
}
