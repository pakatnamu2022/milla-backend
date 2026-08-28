<?php

namespace App\Http\Requests\ap\postventa\repuestos;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;

class UpdateApprovedAccessoriesRequest extends StoreApprovedAccessoriesRequest
{
  public function prepareForValidation(): void
  {
    if ($this->has('type_operation_id')) {
      $typeOperationId = (int) $this->input('type_operation_id');
      $currencyId = match ($typeOperationId) {
        ApMasters::TIPO_OPERACION_COMERCIAL => TypeCurrency::USD_ID,
        ApMasters::TIPO_OPERACION_POSTVENTA => TypeCurrency::PEN_ID,
        default                             => TypeCurrency::PEN_ID,
      };
      $this->merge(['type_currency_id' => $currencyId]);
    }
  }

  protected function ignoreAccessoryId(): ?int
  {
    $route = $this->route('approvedAccessory');

    return is_object($route) ? (int) $route->id : (int) $route;
  }
}
