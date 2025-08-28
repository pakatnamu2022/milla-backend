<?php

namespace App\Http\Resources\TypeCurrency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeCurrencyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'nombre' => $this->nombre,
      'simbolo' => $this->simbolo,
    ];
  }
}
