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
      'status' => $this->status,
      'enable_after_sales' => $this->enable_after_sales,
      'enable_commercial' => $this->enable_commercial,
      'enabled_area' => $this->getEnabledArea(),
      'current_exchange_rate' => $this->getCurrentExchangeRate()
    ];
  }

  private function getEnabledArea()
  {
    $afterSales = $this->enable_after_sales == 1 || $this->enable_after_sales === true;
    $commercial = $this->enable_commercial == 1 || $this->enable_commercial === true;

    if ($afterSales && $commercial) {
      return 'Post-Venta / Comercial';
    } elseif ($afterSales) {
      return 'Post-Venta';
    } elseif ($commercial) {
      return 'Comercial';
    }

    return null;
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
